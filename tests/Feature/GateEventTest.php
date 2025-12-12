<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Gate;
use App\Models\GateEvent;
use App\Models\Role;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_operador_gates_can_view_gate_events(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get('/terrestre/gate-events');

        $response->assertStatus(200);
        $response->assertViewIs('terrestre.gate-events.index');
    }

    public function test_operador_gates_can_create_gate_event(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        $data = [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($user)->post('/terrestre/gate-events', $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('terrestre.gate_event', [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
        ]);
    }

    public function test_transportista_cannot_create_gate_event(): void
    {
        $role = Role::where('code', 'TRANSPORTISTA')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        $data = [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($user)->post('/terrestre/gate-events', $data);

        $response->assertStatus(403);
    }

    public function test_gate_event_requires_valid_data(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->post('/terrestre/gate-events', []);

        $response->assertSessionHasErrors(['gate_id', 'truck_id', 'action', 'event_ts']);
    }

    public function test_gate_event_action_must_be_valid(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        $data = [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'INVALID_ACTION',
            'event_ts' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($user)->post('/terrestre/gate-events', $data);

        $response->assertSessionHasErrors(['action']);
    }

    public function test_gate_event_can_be_linked_to_appointment(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();
        $appointment = Appointment::factory()->create(['truck_id' => $truck->id]);

        $data = [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->toDateTimeString(),
            'cita_id' => $appointment->id,
        ];

        $response = $this->actingAs($user)->post('/terrestre/gate-events', $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('terrestre.gate_event', [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'cita_id' => $appointment->id,
        ]);
    }

    public function test_gate_event_filters_by_date_range(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        // Create events on different dates
        GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'event_ts' => now()->subDays(5),
        ]);

        GateEvent::factory()->create([
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'event_ts' => now(),
        ]);

        $response = $this->actingAs($user)->get('/terrestre/gate-events?fecha_desde=' . now()->subDays(1)->toDateString());

        $response->assertStatus(200);
        $response->assertViewHas('gateEvents');
    }

    public function test_gate_event_audit_log_masks_pii(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create(['placa' => 'ABC-123']);

        $data = [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->toDateTimeString(),
        ];

        $this->actingAs($user)->post('/terrestre/gate-events', $data);

        // Verify audit log was created
        $this->assertDatabaseHas('audit.audit_log', [
            'action' => 'CREATE',
            'object_schema' => 'terrestre',
            'object_table' => 'gate_event',
        ]);

        // Verify PII is masked in audit log
        $auditLog = \App\Models\AuditLog::where('object_table', 'gate_event')
            ->where('action', 'CREATE')
            ->orderBy('event_ts', 'desc')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertStringContainsString('***MASKED***', json_encode($auditLog->details));
        $this->assertStringNotContainsString('ABC-123', json_encode($auditLog->details));
    }

    public function test_gate_event_validates_exit_permit_for_salida(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        // Create digital pass but no exit permit
        \App\Models\DigitalPass::factory()->create([
            'truck_id' => $truck->id,
            'status' => 'ACTIVO',
        ]);

        $data = [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'SALIDA',
            'event_ts' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($user)->post('/terrestre/gate-events', $data);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['validation']);
    }

    public function test_gate_event_allows_salida_with_valid_exit_permit(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        // Create digital pass with exit permit
        $digitalPass = \App\Models\DigitalPass::factory()->create([
            'truck_id' => $truck->id,
            'status' => 'ACTIVO',
        ]);

        \App\Models\AccessPermit::factory()->create([
            'digital_pass_id' => $digitalPass->id,
            'permit_type' => 'SALIDA',
            'status' => 'PENDIENTE',
            'authorized_by' => $user->id,
        ]);

        $data = [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'SALIDA',
            'event_ts' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($user)->post('/terrestre/gate-events', $data);

        $response->assertRedirect(route('gate-events.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('terrestre.gate_event', [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'SALIDA',
        ]);

        // Verify permit was marked as used
        $this->assertDatabaseHas('terrestre.access_permit', [
            'digital_pass_id' => $digitalPass->id,
            'permit_type' => 'SALIDA',
            'status' => 'USADO',
        ]);
    }

    public function test_gate_event_validates_entry_permit_for_entrada(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        // Create digital pass but no entry permit
        \App\Models\DigitalPass::factory()->create([
            'truck_id' => $truck->id,
            'status' => 'ACTIVO',
        ]);

        $data = [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($user)->post('/terrestre/gate-events', $data);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['validation']);
    }

    public function test_gate_event_validates_booking_note_for_cargo_exit(): void
    {
        $role = Role::where('code', 'OPERADOR_GATES')->first();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $gate = Gate::factory()->create();
        $truck = Truck::factory()->create();

        // Create digital pass with exit permit
        $digitalPass = \App\Models\DigitalPass::factory()->create([
            'truck_id' => $truck->id,
            'status' => 'ACTIVO',
        ]);

        // Create cargo item without booking note
        $cargoItem = \App\Models\CargoItem::factory()->create([
            'bl_number' => null, // Missing booking note
            'status' => 'ALMACENADO',
        ]);

        \App\Models\AccessPermit::factory()->create([
            'digital_pass_id' => $digitalPass->id,
            'permit_type' => 'SALIDA',
            'status' => 'PENDIENTE',
            'cargo_item_id' => $cargoItem->id,
            'authorized_by' => $user->id,
        ]);

        $data = [
            'gate_id' => $gate->id,
            'truck_id' => $truck->id,
            'action' => 'SALIDA',
            'event_ts' => now()->toDateTimeString(),
            'extra' => [
                'cargo_item_id' => $cargoItem->id,
            ],
        ];

        $response = $this->actingAs($user)->post('/terrestre/gate-events', $data);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['validation']);
    }
}
