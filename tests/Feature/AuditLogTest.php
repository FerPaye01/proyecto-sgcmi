<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Berth;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Role $role;
    private Vessel $vessel;
    private Berth $berth;

    protected function setUp(): void
    {
        parent::setUp();

        // Create role with permissions
        $this->role = Role::create([
            'code' => 'PLANIFICADOR_PUERTO',
            'name' => 'Planificador Puerto',
        ]);

        $scheduleWritePermission = Permission::create([
            'code' => 'SCHEDULE_WRITE',
            'name' => 'Escribir Programación',
        ]);

        $this->role->permissions()->attach($scheduleWritePermission->id);

        // Create user
        $this->user = User::factory()->create();
        $this->user->roles()->attach($this->role->id);

        // Create test data
        $this->vessel = Vessel::factory()->create();
        $this->berth = Berth::factory()->create();
    }

    public function test_audit_log_created_on_vessel_call_creation(): void
    {
        $this->actingAs($this->user);

        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $this->vessel->id,
            'berth_id' => $this->berth->id,
        ]);

        // Manually trigger audit log
        $auditService = app(\App\Services\AuditService::class);
        $auditService->log(
            action: 'CREATE',
            objectSchema: 'portuario',
            objectTable: 'vessel_call',
            objectId: $vesselCall->id,
            details: [
                'vessel_id' => $vesselCall->vessel_id,
                'viaje_id' => $vesselCall->viaje_id,
                'berth_id' => $vesselCall->berth_id,
            ]
        );

        // Verify audit log was created
        $this->assertDatabaseHas('audit.audit_log', [
            'actor_user' => (string) $this->user->id,
            'action' => 'CREATE',
            'object_schema' => 'portuario',
            'object_table' => 'vessel_call',
            'object_id' => $vesselCall->id,
        ]);

        $auditLog = AuditLog::where('action', 'CREATE')
            ->where('object_table', 'vessel_call')
            ->where('object_id', $vesselCall->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($this->user->id, (int) $auditLog->actor_user);
        $this->assertArrayHasKey('vessel_id', $auditLog->details);
        $this->assertEquals($this->vessel->id, $auditLog->details['vessel_id']);
    }

    public function test_audit_log_created_on_vessel_call_update(): void
    {
        $this->actingAs($this->user);

        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $this->vessel->id,
            'berth_id' => $this->berth->id,
            'estado_llamada' => 'PROGRAMADA',
        ]);

        $oldData = ['estado_llamada' => 'PROGRAMADA'];
        $vesselCall->update(['estado_llamada' => 'EN_TRANSITO']);
        $newData = ['estado_llamada' => 'EN_TRANSITO'];

        // Manually trigger audit log
        $auditService = app(\App\Services\AuditService::class);
        $auditService->log(
            action: 'UPDATE',
            objectSchema: 'portuario',
            objectTable: 'vessel_call',
            objectId: $vesselCall->id,
            details: [
                'old' => $oldData,
                'new' => $newData,
            ]
        );

        // Verify audit log was created
        $this->assertDatabaseHas('audit.audit_log', [
            'actor_user' => (string) $this->user->id,
            'action' => 'UPDATE',
            'object_schema' => 'portuario',
            'object_table' => 'vessel_call',
            'object_id' => $vesselCall->id,
        ]);

        $auditLog = AuditLog::where('action', 'UPDATE')
            ->where('object_table', 'vessel_call')
            ->where('object_id', $vesselCall->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertArrayHasKey('old', $auditLog->details);
        $this->assertArrayHasKey('new', $auditLog->details);
        $this->assertEquals('PROGRAMADA', $auditLog->details['old']['estado_llamada']);
        $this->assertEquals('EN_TRANSITO', $auditLog->details['new']['estado_llamada']);
    }

    public function test_audit_log_created_on_vessel_call_deletion(): void
    {
        $this->actingAs($this->user);

        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $this->vessel->id,
            'berth_id' => $this->berth->id,
        ]);

        $vesselCallId = $vesselCall->id;
        $vesselCallData = [
            'vessel_id' => $vesselCall->vessel_id,
            'viaje_id' => $vesselCall->viaje_id,
        ];

        $vesselCall->delete();

        // Manually trigger audit log
        $auditService = app(\App\Services\AuditService::class);
        $auditService->log(
            action: 'DELETE',
            objectSchema: 'portuario',
            objectTable: 'vessel_call',
            objectId: $vesselCallId,
            details: $vesselCallData
        );

        // Verify audit log was created
        $this->assertDatabaseHas('audit.audit_log', [
            'actor_user' => (string) $this->user->id,
            'action' => 'DELETE',
            'object_schema' => 'portuario',
            'object_table' => 'vessel_call',
            'object_id' => $vesselCallId,
        ]);

        $auditLog = AuditLog::where('action', 'DELETE')
            ->where('object_table', 'vessel_call')
            ->where('object_id', $vesselCallId)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertArrayHasKey('vessel_id', $auditLog->details);
    }

    public function test_audit_service_sanitizes_pii_fields(): void
    {
        $auditService = app(\App\Services\AuditService::class);

        $auditLog = $auditService->log(
            action: 'CREATE',
            objectSchema: 'terrestre',
            objectTable: 'truck',
            objectId: 1,
            details: [
                'placa' => 'ABC-123',
                'company_id' => 5,
            ]
        );

        $this->assertEquals('***MASKED***', $auditLog->details['placa']);
        $this->assertEquals(5, $auditLog->details['company_id']);
    }
}
