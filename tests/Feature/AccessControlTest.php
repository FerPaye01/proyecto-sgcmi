<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AntepuertoQueue;
use App\Models\Appointment;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_view_antepuerto_queue_status(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('antepuerto.queue'));

        $response->assertStatus(200);
        $response->assertViewIs('terrestre.antepuerto.queue');
        $response->assertViewHas('queueEntries');
        $response->assertViewHas('statistics');
    }

    public function test_can_register_antepuerto_entry(): void
    {
        $this->actingAs($this->user);

        $truck = Truck::factory()->create(['activo' => true]);

        $response = $this->post(route('antepuerto.register-entry'), [
            'truck_id' => $truck->id,
            'zone' => 'ANTEPUERTO',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('terrestre.antepuerto_queue', [
            'truck_id' => $truck->id,
            'zone' => 'ANTEPUERTO',
            'status' => 'EN_ESPERA',
        ]);
    }

    public function test_cannot_register_duplicate_entry(): void
    {
        $this->actingAs($this->user);

        $truck = Truck::factory()->create(['activo' => true]);

        // First entry
        AntepuertoQueue::factory()->create([
            'truck_id' => $truck->id,
            'zone' => 'ANTEPUERTO',
            'status' => 'EN_ESPERA',
            'entry_time' => now(),
            'exit_time' => null,
        ]);

        // Try to register again
        $response = $this->post(route('antepuerto.register-entry'), [
            'truck_id' => $truck->id,
            'zone' => 'ANTEPUERTO',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_can_authorize_terminal_entry(): void
    {
        $this->actingAs($this->user);

        $queueEntry = AntepuertoQueue::factory()->create([
            'status' => 'EN_ESPERA',
            'entry_time' => now(),
            'exit_time' => null,
        ]);

        $response = $this->post(route('antepuerto.authorize-entry'), [
            'queue_id' => $queueEntry->id,
            'action' => 'AUTORIZAR',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $queueEntry->refresh();
        $this->assertEquals('AUTORIZADO', $queueEntry->status);
        $this->assertNotNull($queueEntry->exit_time);
    }

    public function test_can_reject_terminal_entry(): void
    {
        $this->actingAs($this->user);

        $queueEntry = AntepuertoQueue::factory()->create([
            'status' => 'EN_ESPERA',
            'entry_time' => now(),
            'exit_time' => null,
        ]);

        $response = $this->post(route('antepuerto.authorize-entry'), [
            'queue_id' => $queueEntry->id,
            'action' => 'RECHAZAR',
            'reason' => 'Documentación incompleta',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $queueEntry->refresh();
        $this->assertEquals('RECHAZADO', $queueEntry->status);
        $this->assertNotNull($queueEntry->exit_time);
    }

    public function test_can_view_zoe_status(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('zoe.status'));

        $response->assertStatus(200);
        $response->assertViewIs('terrestre.zoe.status');
        $response->assertViewHas('queueEntries');
        $response->assertViewHas('statistics');
        $response->assertViewHas('recentHistory');
    }

    public function test_antepuerto_queue_shows_only_antepuerto_entries(): void
    {
        $this->actingAs($this->user);

        // Create entries in different zones
        AntepuertoQueue::factory()->create([
            'zone' => 'ANTEPUERTO',
            'status' => 'EN_ESPERA',
            'entry_time' => now(),
            'exit_time' => null,
        ]);

        AntepuertoQueue::factory()->create([
            'zone' => 'ZOE',
            'status' => 'EN_ESPERA',
            'entry_time' => now(),
            'exit_time' => null,
        ]);

        $response = $this->get(route('antepuerto.queue'));

        $response->assertStatus(200);
        $queueEntries = $response->viewData('queueEntries');
        
        // Should only show ANTEPUERTO entries
        $this->assertEquals(1, $queueEntries->count());
        $this->assertEquals('ANTEPUERTO', $queueEntries->first()->zone);
    }

    public function test_zoe_status_shows_only_zoe_entries(): void
    {
        $this->actingAs($this->user);

        // Create entries in different zones
        AntepuertoQueue::factory()->create([
            'zone' => 'ANTEPUERTO',
            'status' => 'EN_ESPERA',
            'entry_time' => now(),
            'exit_time' => null,
        ]);

        AntepuertoQueue::factory()->create([
            'zone' => 'ZOE',
            'status' => 'EN_ESPERA',
            'entry_time' => now(),
            'exit_time' => null,
        ]);

        $response = $this->get(route('zoe.status'));

        $response->assertStatus(200);
        $queueEntries = $response->viewData('queueEntries');
        
        // Should only show ZOE entries
        $this->assertEquals(1, $queueEntries->count());
        $this->assertEquals('ZOE', $queueEntries->first()->zone);
    }

    public function test_statistics_are_calculated_correctly(): void
    {
        $this->actingAs($this->user);

        // Create entries with different waiting times
        AntepuertoQueue::factory()->create([
            'zone' => 'ANTEPUERTO',
            'status' => 'EN_ESPERA',
            'entry_time' => now()->subMinutes(30),
            'exit_time' => null,
        ]);

        AntepuertoQueue::factory()->create([
            'zone' => 'ANTEPUERTO',
            'status' => 'EN_ESPERA',
            'entry_time' => now()->subMinutes(60),
            'exit_time' => null,
        ]);

        $response = $this->get(route('antepuerto.queue'));

        $response->assertStatus(200);
        $statistics = $response->viewData('statistics');
        
        $this->assertEquals(2, $statistics['total_waiting']);
        $this->assertGreaterThan(0, $statistics['avg_waiting_time']);
        $this->assertGreaterThan(0, $statistics['max_waiting_time']);
    }

    public function test_can_create_access_permit(): void
    {
        $this->actingAs($this->user);

        $digitalPass = \App\Models\DigitalPass::factory()->create([
            'status' => 'ACTIVO',
            'valid_until' => now()->addDays(7),
        ]);

        $response = $this->post(route('access-permit.store'), [
            'digital_pass_id' => $digitalPass->id,
            'permit_type' => 'SALIDA',
        ]);

        $response->assertRedirect(route('access-permit.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('terrestre.access_permit', [
            'digital_pass_id' => $digitalPass->id,
            'permit_type' => 'SALIDA',
            'status' => 'PENDIENTE',
        ]);
    }

    public function test_cannot_create_permit_with_expired_digital_pass(): void
    {
        $this->actingAs($this->user);

        $digitalPass = \App\Models\DigitalPass::factory()->create([
            'status' => 'ACTIVO',
            'valid_until' => now()->subDays(1), // Expired
        ]);

        $response = $this->post(route('access-permit.store'), [
            'digital_pass_id' => $digitalPass->id,
            'permit_type' => 'SALIDA',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_access_permit_validation_requires_exit_permit_for_salida(): void
    {
        $this->actingAs($this->user);

        $digitalPass = \App\Models\DigitalPass::factory()->create([
            'status' => 'ACTIVO',
        ]);

        // Try to validate without exit permit
        $response = $this->postJson(route('access-permit.validate'), [
            'digital_pass_id' => $digitalPass->id,
            'action' => 'SALIDA',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['valid' => false]);
        $response->assertJsonPath('errors.0', 'Falta Permiso de Salida válido');
    }

    public function test_access_permit_validation_passes_with_valid_exit_permit(): void
    {
        $this->actingAs($this->user);

        $digitalPass = \App\Models\DigitalPass::factory()->create([
            'status' => 'ACTIVO',
        ]);

        // Create exit permit
        \App\Models\AccessPermit::factory()->create([
            'digital_pass_id' => $digitalPass->id,
            'permit_type' => 'SALIDA',
            'status' => 'PENDIENTE',
        ]);

        $response = $this->postJson(route('access-permit.validate'), [
            'digital_pass_id' => $digitalPass->id,
            'action' => 'SALIDA',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['valid' => true]);
    }

    public function test_access_permit_validation_requires_entry_permit_for_entrada(): void
    {
        $this->actingAs($this->user);

        $digitalPass = \App\Models\DigitalPass::factory()->create([
            'status' => 'ACTIVO',
        ]);

        // Try to validate without entry permit
        $response = $this->postJson(route('access-permit.validate'), [
            'digital_pass_id' => $digitalPass->id,
            'action' => 'ENTRADA',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['valid' => false]);
        $response->assertJsonPath('errors.0', 'Falta Autorización de Ingreso válida');
    }
}
