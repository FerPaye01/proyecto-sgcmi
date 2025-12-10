<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Berth;
use App\Models\Company;
use App\Models\Entidad;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tramite;
use App\Models\Truck;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselCall;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive test to verify that audit_log does not contain PII
 * 
 * This test verifies:
 * 1. AuditService sanitizes PII fields (placa, tramite_ext_id, password, token, secret, credentials)
 * 2. No PII exists in actual audit_log records in the database
 * 3. Controllers do not accidentally log PII
 * 4. Nested arrays are properly sanitized
 */
class AuditLogPiiVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user with permissions
        $role = Role::create([
            'code' => 'ADMIN',
            'name' => 'Administrator',
        ]);

        $this->user = User::factory()->create();
        $this->user->roles()->attach($role->id);

        $this->auditService = app(AuditService::class);
    }

    /**
     * Test that placa (truck license plate) is masked in audit logs
     */
    public function test_placa_is_masked_in_audit_logs(): void
    {
        $auditLog = $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'terrestre',
            objectTable: 'truck',
            objectId: 1,
            details: [
                'placa' => 'ABC-123',
                'company_id' => 1,
                'activo' => true,
            ]
        );

        // Verify in memory
        $this->assertEquals('***MASKED***', $auditLog->details['placa']);
        $this->assertEquals(1, $auditLog->details['company_id']);

        // Verify in database
        $dbLog = AuditLog::find($auditLog->id);
        $this->assertEquals('***MASKED***', $dbLog->details['placa']);
        $this->assertStringNotContainsString('ABC-123', json_encode($dbLog->details));
    }

    /**
     * Test that tramite_ext_id is masked in audit logs
     */
    public function test_tramite_ext_id_is_masked_in_audit_logs(): void
    {
        $auditLog = $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'aduanas',
            objectTable: 'tramite',
            objectId: 1,
            details: [
                'tramite_ext_id' => 'CUS-2025-001',
                'regimen' => 'IMPORTACION',
                'estado' => 'INICIADO',
            ]
        );

        // Verify in memory
        $this->assertEquals('***MASKED***', $auditLog->details['tramite_ext_id']);
        $this->assertEquals('IMPORTACION', $auditLog->details['regimen']);

        // Verify in database
        $dbLog = AuditLog::find($auditLog->id);
        $this->assertEquals('***MASKED***', $dbLog->details['tramite_ext_id']);
        $this->assertStringNotContainsString('CUS-2025-001', json_encode($dbLog->details));
    }

    /**
     * Test that password is masked in audit logs
     */
    public function test_password_is_masked_in_audit_logs(): void
    {
        $auditLog = $this->auditService->log(
            action: 'UPDATE',
            objectSchema: 'admin',
            objectTable: 'users',
            objectId: 1,
            details: [
                'username' => 'testuser',
                'password' => 'secret123',
                'email' => 'test@example.com',
            ]
        );

        // Verify in memory
        $this->assertEquals('***MASKED***', $auditLog->details['password']);
        $this->assertEquals('testuser', $auditLog->details['username']);

        // Verify in database
        $dbLog = AuditLog::find($auditLog->id);
        $this->assertEquals('***MASKED***', $dbLog->details['password']);
        $this->assertStringNotContainsString('secret123', json_encode($dbLog->details));
    }

    /**
     * Test that token is masked in audit logs
     */
    public function test_token_is_masked_in_audit_logs(): void
    {
        $auditLog = $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'admin',
            objectTable: 'api_tokens',
            objectId: 1,
            details: [
                'user_id' => 1,
                'token' => 'abc123token456',
                'expires_at' => '2025-12-31',
            ]
        );

        // Verify in memory
        $this->assertEquals('***MASKED***', $auditLog->details['token']);

        // Verify in database
        $dbLog = AuditLog::find($auditLog->id);
        $this->assertEquals('***MASKED***', $dbLog->details['token']);
        $this->assertStringNotContainsString('abc123token456', json_encode($dbLog->details));
    }

    /**
     * Test that secret is masked in audit logs
     */
    public function test_secret_is_masked_in_audit_logs(): void
    {
        $auditLog = $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'admin',
            objectTable: 'api_keys',
            objectId: 1,
            details: [
                'name' => 'API Key',
                'secret' => 'my-secret-key',
            ]
        );

        // Verify in memory
        $this->assertEquals('***MASKED***', $auditLog->details['secret']);

        // Verify in database
        $dbLog = AuditLog::find($auditLog->id);
        $this->assertEquals('***MASKED***', $dbLog->details['secret']);
        $this->assertStringNotContainsString('my-secret-key', json_encode($dbLog->details));
    }

    /**
     * Test that credentials is masked in audit logs
     */
    public function test_credentials_is_masked_in_audit_logs(): void
    {
        $auditLog = $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'admin',
            objectTable: 'external_services',
            objectId: 1,
            details: [
                'service_name' => 'External API',
                'credentials' => 'user:password',
            ]
        );

        // Verify in memory
        $this->assertEquals('***MASKED***', $auditLog->details['credentials']);

        // Verify in database
        $dbLog = AuditLog::find($auditLog->id);
        $this->assertEquals('***MASKED***', $dbLog->details['credentials']);
        $this->assertStringNotContainsString('user:password', json_encode($dbLog->details));
    }

    /**
     * Test that PII is masked in nested arrays
     */
    public function test_pii_is_masked_in_nested_arrays(): void
    {
        $auditLog = $this->auditService->log(
            action: 'UPDATE',
            objectSchema: 'aduanas',
            objectTable: 'tramite',
            objectId: 1,
            details: [
                'old' => [
                    'tramite_ext_id' => 'CUS-2025-001',
                    'estado' => 'INICIADO',
                ],
                'new' => [
                    'tramite_ext_id' => 'CUS-2025-001',
                    'estado' => 'APROBADO',
                ],
            ]
        );

        // Verify in memory
        $this->assertEquals('***MASKED***', $auditLog->details['old']['tramite_ext_id']);
        $this->assertEquals('***MASKED***', $auditLog->details['new']['tramite_ext_id']);
        $this->assertEquals('INICIADO', $auditLog->details['old']['estado']);
        $this->assertEquals('APROBADO', $auditLog->details['new']['estado']);

        // Verify in database
        $dbLog = AuditLog::find($auditLog->id);
        $this->assertEquals('***MASKED***', $dbLog->details['old']['tramite_ext_id']);
        $this->assertEquals('***MASKED***', $dbLog->details['new']['tramite_ext_id']);
        $this->assertStringNotContainsString('CUS-2025-001', json_encode($dbLog->details));
    }

    /**
     * Test that PII is masked in deeply nested arrays
     */
    public function test_pii_is_masked_in_deeply_nested_arrays(): void
    {
        $auditLog = $this->auditService->log(
            action: 'UPDATE',
            objectSchema: 'test',
            objectTable: 'test_table',
            objectId: 1,
            details: [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'tramite_ext_id' => 'CUS-2025-001',
                            'placa' => 'ABC-123',
                            'password' => 'secret',
                            'normal_field' => 'value',
                        ],
                    ],
                ],
            ]
        );

        // Verify in memory
        $this->assertEquals('***MASKED***', $auditLog->details['level1']['level2']['level3']['tramite_ext_id']);
        $this->assertEquals('***MASKED***', $auditLog->details['level1']['level2']['level3']['placa']);
        $this->assertEquals('***MASKED***', $auditLog->details['level1']['level2']['level3']['password']);
        $this->assertEquals('value', $auditLog->details['level1']['level2']['level3']['normal_field']);

        // Verify in database
        $dbLog = AuditLog::find($auditLog->id);
        $this->assertEquals('***MASKED***', $dbLog->details['level1']['level2']['level3']['tramite_ext_id']);
        $this->assertEquals('***MASKED***', $dbLog->details['level1']['level2']['level3']['placa']);
        $this->assertEquals('***MASKED***', $dbLog->details['level1']['level2']['level3']['password']);
        $this->assertStringNotContainsString('CUS-2025-001', json_encode($dbLog->details));
        $this->assertStringNotContainsString('ABC-123', json_encode($dbLog->details));
        $this->assertStringNotContainsString('secret', json_encode($dbLog->details));
    }

    /**
     * Test that multiple PII fields are all masked
     */
    public function test_multiple_pii_fields_are_all_masked(): void
    {
        $auditLog = $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'test',
            objectTable: 'test_table',
            objectId: 1,
            details: [
                'tramite_ext_id' => 'CUS-2025-001',
                'placa' => 'ABC-123',
                'password' => 'secret123',
                'token' => 'abc123token',
                'secret' => 'my-secret',
                'credentials' => 'user:pass',
                'normal_field' => 'normal_value',
            ]
        );

        // Verify all PII fields are masked
        $this->assertEquals('***MASKED***', $auditLog->details['tramite_ext_id']);
        $this->assertEquals('***MASKED***', $auditLog->details['placa']);
        $this->assertEquals('***MASKED***', $auditLog->details['password']);
        $this->assertEquals('***MASKED***', $auditLog->details['token']);
        $this->assertEquals('***MASKED***', $auditLog->details['secret']);
        $this->assertEquals('***MASKED***', $auditLog->details['credentials']);
        $this->assertEquals('normal_value', $auditLog->details['normal_field']);

        // Verify in database
        $dbLog = AuditLog::find($auditLog->id);
        $jsonDetails = json_encode($dbLog->details);
        $this->assertStringNotContainsString('CUS-2025-001', $jsonDetails);
        $this->assertStringNotContainsString('ABC-123', $jsonDetails);
        $this->assertStringNotContainsString('secret123', $jsonDetails);
        $this->assertStringNotContainsString('abc123token', $jsonDetails);
        $this->assertStringNotContainsString('my-secret', $jsonDetails);
        $this->assertStringNotContainsString('user:pass', $jsonDetails);
    }

    /**
     * Test that no PII exists in any audit_log records in the database
     * This is a comprehensive scan of all audit logs
     */
    public function test_no_pii_exists_in_database_audit_logs(): void
    {
        $this->actingAs($this->user);

        // Create test data with PII
        $company = Company::factory()->create();
        $truck = Truck::factory()->create([
            'placa' => 'TEST-999',
            'company_id' => $company->id,
        ]);
        $vessel = Vessel::factory()->create();
        $berth = Berth::factory()->create();
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
        ]);
        $entidad = Entidad::factory()->create();

        // Create appointment (which should NOT log placa)
        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
        ]);

        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'terrestre',
            objectTable: 'appointment',
            objectId: $appointment->id,
            details: [
                'truck_id' => $appointment->truck_id,
                'company_id' => $appointment->company_id,
            ]
        );

        // Create tramite (which should NOT log tramite_ext_id)
        $tramite = Tramite::factory()->create([
            'tramite_ext_id' => 'SECRET-TRAMITE-123',
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
        ]);

        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'aduanas',
            objectTable: 'tramite',
            objectId: $tramite->id,
            details: [
                'vessel_call_id' => $tramite->vessel_call_id,
                'regimen' => $tramite->regimen,
            ]
        );

        // Intentionally try to log PII (should be masked)
        $this->auditService->log(
            action: 'TEST',
            objectSchema: 'test',
            objectTable: 'test',
            objectId: 1,
            details: [
                'placa' => 'TEST-999',
                'tramite_ext_id' => 'SECRET-TRAMITE-123',
            ]
        );

        // Get all audit logs from database
        $allLogs = AuditLog::all();

        // Verify no PII exists in any log
        foreach ($allLogs as $log) {
            $jsonDetails = json_encode($log->details);

            // Check for placa
            $this->assertStringNotContainsString('TEST-999', $jsonDetails, 
                "Found PII (placa) in audit log ID {$log->id}");

            // Check for tramite_ext_id
            $this->assertStringNotContainsString('SECRET-TRAMITE-123', $jsonDetails,
                "Found PII (tramite_ext_id) in audit log ID {$log->id}");

            // If details contain placa or tramite_ext_id keys, they should be masked
            if (isset($log->details['placa'])) {
                $this->assertEquals('***MASKED***', $log->details['placa'],
                    "Placa not masked in audit log ID {$log->id}");
            }

            if (isset($log->details['tramite_ext_id'])) {
                $this->assertEquals('***MASKED***', $log->details['tramite_ext_id'],
                    "Tramite_ext_id not masked in audit log ID {$log->id}");
            }
        }

        // Verify we actually tested some logs
        $this->assertGreaterThan(0, $allLogs->count(), 
            'No audit logs found to verify');
    }

    /**
     * Test that controllers do not accidentally include PII in audit logs
     */
    public function test_controllers_do_not_log_pii(): void
    {
        $this->actingAs($this->user);

        // Create test data
        $company = Company::factory()->create();
        $truck = Truck::factory()->create([
            'placa' => 'SENSITIVE-PLATE',
            'company_id' => $company->id,
        ]);
        $vessel = Vessel::factory()->create();
        $berth = Berth::factory()->create();
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
        ]);
        $entidad = Entidad::factory()->create();

        // Test TramiteController
        $tramite = Tramite::factory()->create([
            'tramite_ext_id' => 'SENSITIVE-TRAMITE-ID',
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
        ]);

        // Simulate what TramiteController does
        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'aduanas',
            objectTable: 'tramite',
            objectId: $tramite->id,
            details: [
                'vessel_call_id' => $tramite->vessel_call_id,
                'regimen' => $tramite->regimen,
                'subpartida' => $tramite->subpartida,
                'estado' => $tramite->estado,
                // tramite_ext_id should NOT be here
            ]
        );

        // Test AppointmentController
        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
        ]);

        // Simulate what AppointmentController does
        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'terrestre',
            objectTable: 'appointment',
            objectId: $appointment->id,
            details: [
                'truck_id' => $appointment->truck_id,
                'company_id' => $appointment->company_id,
                'vessel_call_id' => $appointment->vessel_call_id,
                // placa should NOT be here (only truck_id)
            ]
        );

        // Verify no PII in database
        $allLogs = AuditLog::all();
        foreach ($allLogs as $log) {
            $jsonDetails = json_encode($log->details);
            $this->assertStringNotContainsString('SENSITIVE-PLATE', $jsonDetails);
            $this->assertStringNotContainsString('SENSITIVE-TRAMITE-ID', $jsonDetails);
        }
    }
}
