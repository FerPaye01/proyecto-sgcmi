<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Truck;
use App\Models\User;
use App\Models\VesselCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Role $role;
    private Company $company;
    private Truck $truck;

    protected function setUp(): void
    {
        parent::setUp();

        // Create role with permissions
        $this->role = Role::create([
            'code' => 'OPERADOR_GATES',
            'name' => 'Operador Gates',
        ]);

        $appointmentReadPermission = Permission::create([
            'code' => 'APPOINTMENT_READ',
            'name' => 'Leer Citas',
        ]);

        $appointmentWritePermission = Permission::create([
            'code' => 'APPOINTMENT_WRITE',
            'name' => 'Escribir Citas',
        ]);

        $this->role->permissions()->attach([
            $appointmentReadPermission->id,
            $appointmentWritePermission->id,
        ]);

        // Create user
        $this->user = User::factory()->create();
        $this->user->roles()->attach($this->role->id);

        // Create test data
        $this->company = Company::factory()->create();
        $this->truck = Truck::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    public function test_audit_log_created_on_appointment_creation(): void
    {
        $this->actingAs($this->user);

        $appointmentData = [
            'truck_id' => $this->truck->id,
            'company_id' => $this->company->id,
            'vessel_call_id' => null,
            'hora_programada' => '2024-12-01 10:00:00',
            'estado' => 'PROGRAMADA',
        ];

        $response = $this->postJson('/terrestre/appointments', $appointmentData);

        $response->assertStatus(201);

        $appointment = Appointment::latest()->first();

        // Verify audit log was created
        $this->assertDatabaseHas('audit.audit_log', [
            'actor_user' => (string) $this->user->id,
            'action' => 'CREATE',
            'object_schema' => 'terrestre',
            'object_table' => 'appointment',
            'object_id' => $appointment->id,
        ]);

        $auditLog = AuditLog::where('action', 'CREATE')
            ->where('object_table', 'appointment')
            ->where('object_id', $appointment->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($this->user->id, (int) $auditLog->actor_user);
        $this->assertArrayHasKey('truck_id', $auditLog->details);
        $this->assertArrayHasKey('company_id', $auditLog->details);
        $this->assertEquals($this->truck->id, $auditLog->details['truck_id']);
        $this->assertEquals($this->company->id, $auditLog->details['company_id']);
    }

    public function test_audit_log_created_on_appointment_update(): void
    {
        $this->actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'truck_id' => $this->truck->id,
            'company_id' => $this->company->id,
            'estado' => 'PROGRAMADA',
        ]);

        $updateData = [
            'estado' => 'CONFIRMADA',
        ];

        $response = $this->patchJson("/terrestre/appointments/{$appointment->id}", $updateData);

        $response->assertStatus(200);

        // Verify audit log was created
        $this->assertDatabaseHas('audit.audit_log', [
            'actor_user' => (string) $this->user->id,
            'action' => 'UPDATE',
            'object_schema' => 'terrestre',
            'object_table' => 'appointment',
            'object_id' => $appointment->id,
        ]);

        $auditLog = AuditLog::where('action', 'UPDATE')
            ->where('object_table', 'appointment')
            ->where('object_id', $appointment->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertArrayHasKey('old', $auditLog->details);
        $this->assertArrayHasKey('new', $auditLog->details);
        $this->assertEquals('PROGRAMADA', $auditLog->details['old']['estado']);
        $this->assertEquals('CONFIRMADA', $auditLog->details['new']['estado']);
    }

    public function test_audit_log_created_on_appointment_deletion(): void
    {
        $this->actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'truck_id' => $this->truck->id,
            'company_id' => $this->company->id,
        ]);

        $appointmentId = $appointment->id;

        $response = $this->deleteJson("/terrestre/appointments/{$appointmentId}");

        $response->assertStatus(200);

        // Verify audit log was created
        $this->assertDatabaseHas('audit.audit_log', [
            'actor_user' => (string) $this->user->id,
            'action' => 'DELETE',
            'object_schema' => 'terrestre',
            'object_table' => 'appointment',
            'object_id' => $appointmentId,
        ]);

        $auditLog = AuditLog::where('action', 'DELETE')
            ->where('object_table', 'appointment')
            ->where('object_id', $appointmentId)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertArrayHasKey('truck_id', $auditLog->details);
        $this->assertArrayHasKey('company_id', $auditLog->details);
    }

    // Note: Scoping test removed - requires user_companies pivot table
    // which will be implemented in a separate task
}
