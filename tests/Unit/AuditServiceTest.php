<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_service_can_be_instantiated(): void
    {
        $service = app(AuditService::class);
        
        $this->assertInstanceOf(AuditService::class, $service);
    }

    public function test_audit_service_sanitizes_pii_in_top_level(): void
    {
        $service = app(AuditService::class);
        
        $auditLog = $service->log(
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
        
        $this->assertNotNull($auditLog);
        $this->assertEquals('***MASKED***', $auditLog->details['tramite_ext_id']);
        $this->assertEquals('IMPORTACION', $auditLog->details['regimen']);
        $this->assertEquals('INICIADO', $auditLog->details['estado']);
    }

    public function test_audit_service_sanitizes_pii_in_nested_arrays(): void
    {
        $service = app(AuditService::class);
        
        $auditLog = $service->log(
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
        
        $this->assertNotNull($auditLog);
        $this->assertEquals('***MASKED***', $auditLog->details['old']['tramite_ext_id']);
        $this->assertEquals('***MASKED***', $auditLog->details['new']['tramite_ext_id']);
        $this->assertEquals('INICIADO', $auditLog->details['old']['estado']);
        $this->assertEquals('APROBADO', $auditLog->details['new']['estado']);
    }

    public function test_audit_service_sanitizes_placa_pii(): void
    {
        $service = app(AuditService::class);
        
        $auditLog = $service->log(
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
        
        $this->assertNotNull($auditLog);
        $this->assertEquals('***MASKED***', $auditLog->details['placa']);
        $this->assertEquals(1, $auditLog->details['company_id']);
        $this->assertTrue($auditLog->details['activo']);
    }

    public function test_audit_service_sanitizes_multiple_pii_fields(): void
    {
        $service = app(AuditService::class);
        
        $auditLog = $service->log(
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
        
        $this->assertNotNull($auditLog);
        $this->assertEquals('***MASKED***', $auditLog->details['tramite_ext_id']);
        $this->assertEquals('***MASKED***', $auditLog->details['placa']);
        $this->assertEquals('***MASKED***', $auditLog->details['password']);
        $this->assertEquals('***MASKED***', $auditLog->details['token']);
        $this->assertEquals('***MASKED***', $auditLog->details['secret']);
        $this->assertEquals('***MASKED***', $auditLog->details['credentials']);
        $this->assertEquals('normal_value', $auditLog->details['normal_field']);
    }

    public function test_audit_service_sanitizes_deeply_nested_pii(): void
    {
        $service = app(AuditService::class);
        
        $auditLog = $service->log(
            action: 'UPDATE',
            objectSchema: 'test',
            objectTable: 'test_table',
            objectId: 1,
            details: [
                'level1' => [
                    'level2' => [
                        'tramite_ext_id' => 'CUS-2025-001',
                        'placa' => 'ABC-123',
                        'normal_field' => 'value',
                    ],
                ],
            ]
        );
        
        $this->assertNotNull($auditLog);
        $this->assertEquals('***MASKED***', $auditLog->details['level1']['level2']['tramite_ext_id']);
        $this->assertEquals('***MASKED***', $auditLog->details['level1']['level2']['placa']);
        $this->assertEquals('value', $auditLog->details['level1']['level2']['normal_field']);
    }
}
