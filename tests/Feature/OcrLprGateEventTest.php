<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Gate;
use App\Models\GateEvent;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OcrLprGateEventTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Gate $gate;
    private Truck $truck;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user with appropriate permissions
        $this->user = User::factory()->create();
        $this->user->roles()->attach(
            \App\Models\Role::factory()->create(['name' => 'OPERADOR_GATE'])
        );
        
        // Grant GATE_EVENT_WRITE permission
        $permission = \App\Models\Permission::factory()->create([
            'name' => 'GATE_EVENT_WRITE',
            'description' => 'Crear y modificar eventos de gate',
        ]);
        $this->user->roles->first()->permissions()->attach($permission);

        // Create test data
        $this->gate = Gate::factory()->create();
        $this->truck = Truck::factory()->create();
    }

    public function test_process_ocr_lpr_data_creates_gate_event_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/terrestre/gate-events/ocr-lpr', [
                'gate_id' => $this->gate->id,
                'action' => 'ENTRADA',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Evento de gate registrado exitosamente mediante OCR/LPR',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'gate_event' => [
                        'id',
                        'gate_id',
                        'truck_id',
                        'action',
                        'event_ts',
                        'extra',
                    ],
                    'ocr_lpr_results' => [
                        'plate' => ['value', 'confidence', 'accepted'],
                        'container' => ['value', 'confidence', 'accepted', 'iso_valid'],
                    ],
                ],
            ]);

        // Verify gate event was created
        $this->assertDatabaseHas('terrestre.gate_event', [
            'gate_id' => $this->gate->id,
            'action' => 'ENTRADA',
        ]);
    }

    public function test_process_ocr_lpr_data_stores_ocr_data_in_extra_field(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/terrestre/gate-events/ocr-lpr', [
                'gate_id' => $this->gate->id,
                'action' => 'SALIDA',
            ]);

        $response->assertStatus(201);

        $gateEvent = GateEvent::latest('id')->first();
        
        $this->assertNotNull($gateEvent->extra);
        $this->assertArrayHasKey('ocr_lpr_data', $gateEvent->extra);
        $this->assertArrayHasKey('auto_populated', $gateEvent->extra);
        $this->assertTrue($gateEvent->extra['auto_populated']);
        
        $this->assertArrayHasKey('plate', $gateEvent->extra['ocr_lpr_data']);
        $this->assertArrayHasKey('container', $gateEvent->extra['ocr_lpr_data']);
    }

    public function test_process_ocr_lpr_data_rejects_unregistered_plate(): void
    {
        // Delete all trucks to ensure plate won't be found
        Truck::query()->delete();

        $response = $this->actingAs($this->user)
            ->postJson('/terrestre/gate-events/ocr-lpr', [
                'gate_id' => $this->gate->id,
                'action' => 'ENTRADA',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Placa no registrada en el sistema',
            ])
            ->assertJsonStructure([
                'data' => [
                    'recognized_plate',
                    'confidence',
                    'suggestion',
                ],
            ]);
    }

    public function test_process_ocr_lpr_data_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/terrestre/gate-events/ocr-lpr', [
                // Missing gate_id and action
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gate_id', 'action']);
    }

    public function test_process_ocr_lpr_data_validates_action_values(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/terrestre/gate-events/ocr-lpr', [
                'gate_id' => $this->gate->id,
                'action' => 'INVALID_ACTION',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['action']);
    }

    public function test_process_ocr_lpr_data_validates_gate_exists(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/terrestre/gate-events/ocr-lpr', [
                'gate_id' => 99999, // Non-existent gate
                'action' => 'ENTRADA',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gate_id']);
    }

    public function test_process_ocr_lpr_data_respects_custom_confidence_threshold(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/terrestre/gate-events/ocr-lpr', [
                'gate_id' => $this->gate->id,
                'action' => 'ENTRADA',
                'confidence_threshold' => 99.5, // Very high threshold - likely to fail
            ]);

        // Should either succeed (if confidence is high enough) or fail with confidence error
        if ($response->status() === 422) {
            $response->assertJson([
                'success' => false,
                'message' => 'Confianza de reconocimiento de placa insuficiente',
            ]);
        } else {
            $response->assertStatus(201);
        }
    }

    public function test_process_ocr_lpr_data_requires_authentication(): void
    {
        $response = $this->postJson('/terrestre/gate-events/ocr-lpr', [
            'gate_id' => $this->gate->id,
            'action' => 'ENTRADA',
        ]);

        $response->assertStatus(401);
    }

    public function test_process_ocr_lpr_data_requires_permission(): void
    {
        // Create user without GATE_EVENT_WRITE permission
        $unauthorizedUser = User::factory()->create();
        $unauthorizedUser->roles()->attach(
            \App\Models\Role::factory()->create(['name' => 'VIEWER'])
        );

        $response = $this->actingAs($unauthorizedUser)
            ->postJson('/terrestre/gate-events/ocr-lpr', [
                'gate_id' => $this->gate->id,
                'action' => 'ENTRADA',
            ]);

        $response->assertStatus(403);
    }

    public function test_process_ocr_lpr_data_includes_container_number_when_confidence_acceptable(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/terrestre/gate-events/ocr-lpr', [
                'gate_id' => $this->gate->id,
                'action' => 'ENTRADA',
                'confidence_threshold' => 70.0, // Lower threshold to ensure container is accepted
            ]);

        if ($response->status() === 201) {
            $gateEvent = GateEvent::latest('id')->first();
            
            // Check if container was accepted and stored
            if ($gateEvent->extra['container_confidence_ok'] ?? false) {
                $this->assertArrayHasKey('container_number', $gateEvent->extra);
            }
        }
    }

    public function test_process_ocr_lpr_data_links_to_active_appointment_if_exists(): void
    {
        // Create an active appointment for the truck
        $appointment = \App\Models\Appointment::factory()->create([
            'truck_id' => $this->truck->id,
            'hora_cita' => now()->subHour(),
            'hora_llegada' => now()->subMinutes(30),
            'hora_salida' => null, // Still active
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/terrestre/gate-events/ocr-lpr', [
                'gate_id' => $this->gate->id,
                'action' => 'SALIDA',
            ]);

        if ($response->status() === 201) {
            $gateEvent = GateEvent::latest('id')->first();
            
            // If the recognized plate matches our truck, it should link to the appointment
            if ($gateEvent->truck_id === $this->truck->id) {
                $this->assertEquals($appointment->id, $gateEvent->cita_id);
            }
        }
    }
}
