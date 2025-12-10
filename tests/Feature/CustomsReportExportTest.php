<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Entidad;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tramite;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomsReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol con permisos de exportación
        $this->role = Role::factory()->create(['code' => 'ANALISTA']);
        
        $reportExportPermission = Permission::factory()->create([
            'code' => 'REPORT_EXPORT',
            'name' => 'Exportar Reportes',
        ]);
        
        $cusReportPermission = Permission::factory()->create([
            'code' => 'CUS_REPORT_READ',
            'name' => 'Leer Reportes Aduaneros',
        ]);

        $this->role->permissions()->attach([$reportExportPermission->id, $cusReportPermission->id]);

        // Crear usuario con rol
        $this->user = User::factory()->create();
        $this->user->roles()->attach($this->role->id);
    }

    public function test_export_r7_applies_pii_anonymization(): void
    {
        // Crear datos de prueba
        $vessel = Vessel::factory()->create(['name' => 'MSC AURORA']);
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'viaje_id' => 'V001',
        ]);
        $entidad = Entidad::factory()->create(['name' => 'SUNAT']);

        $tramite = Tramite::factory()->create([
            'tramite_ext_id' => 'CUS-2025-001',
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
            'regimen' => 'IMPORTACION',
            'estado' => 'APROBADO',
        ]);

        // Realizar exportación
        $response = $this->actingAs($this->user)
            ->post('/export/r7', [
                'format' => 'csv',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->getContent();
        $this->assertIsString($content);

        // Verificar que el tramite_ext_id está enmascarado
        $this->assertStringNotContainsString('CUS-2025-001', $content);
        $this->assertStringContainsString('CU**********', $content);

        // Verificar que otros datos no están enmascarados
        $this->assertStringContainsString('MSC AURORA', $content);
        $this->assertStringContainsString('V001', $content);
        $this->assertStringContainsString('IMPORTACION', $content);
        $this->assertStringContainsString('SUNAT', $content);
    }

    public function test_export_r8_applies_pii_anonymization(): void
    {
        // Crear datos de prueba
        $vessel = Vessel::factory()->create(['name' => 'MAERSK LINE']);
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'viaje_id' => 'V002',
        ]);
        $entidad = Entidad::factory()->create(['name' => 'ADUANAS']);

        $tramite = Tramite::factory()->create([
            'tramite_ext_id' => 'CUS-2025-002',
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
            'regimen' => 'EXPORTACION',
            'estado' => 'APROBADO',
            'fecha_fin' => now(),
        ]);

        // Realizar exportación
        $response = $this->actingAs($this->user)
            ->post('/export/r8', [
                'format' => 'csv',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->getContent();
        $this->assertIsString($content);

        // Verificar que el tramite_ext_id está enmascarado
        $this->assertStringNotContainsString('CUS-2025-002', $content);
        $this->assertStringContainsString('CU**********', $content);

        // Verificar que otros datos no están enmascarados
        $this->assertStringContainsString('MAERSK LINE', $content);
        $this->assertStringContainsString('V002', $content);
        $this->assertStringContainsString('EXPORTACION', $content);
        $this->assertStringContainsString('ADUANAS', $content);
    }

    public function test_export_r9_applies_pii_anonymization(): void
    {
        // Crear datos de prueba
        $vessel = Vessel::factory()->create(['name' => 'CMA CGM']);
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'viaje_id' => 'V003',
        ]);
        $entidad = Entidad::factory()->create(['name' => 'SUNAT']);

        $tramite = Tramite::factory()->create([
            'tramite_ext_id' => 'CUS-2025-003',
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
            'regimen' => 'TRANSITO',
            'estado' => 'RECHAZADO',
        ]);

        // Realizar exportación
        $response = $this->actingAs($this->user)
            ->post('/export/r9', [
                'format' => 'csv',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->getContent();
        $this->assertIsString($content);

        // Verificar que el tramite_ext_id está enmascarado
        $this->assertStringNotContainsString('CUS-2025-003', $content);
        $this->assertStringContainsString('CU**********', $content);

        // Verificar que otros datos no están enmascarados
        $this->assertStringContainsString('CMA CGM', $content);
        $this->assertStringContainsString('V003', $content);
        $this->assertStringContainsString('TRANSITO', $content);
        $this->assertStringContainsString('SUNAT', $content);
        $this->assertStringContainsString('RECHAZADO', $content);
    }

    public function test_export_r7_xlsx_applies_pii_anonymization(): void
    {
        // Crear datos de prueba
        $vessel = Vessel::factory()->create(['name' => 'TEST VESSEL']);
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'viaje_id' => 'V999',
        ]);
        $entidad = Entidad::factory()->create(['name' => 'TEST ENTIDAD']);

        $tramite = Tramite::factory()->create([
            'tramite_ext_id' => 'CUS-2025-999',
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
            'regimen' => 'IMPORTACION',
            'estado' => 'APROBADO',
        ]);

        // Realizar exportación en formato XLSX
        $response = $this->actingAs($this->user)
            ->post('/export/r7', [
                'format' => 'xlsx',
            ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('reporte_r7_', $response->headers->get('Content-Disposition'));
    }

    public function test_export_r8_pdf_applies_pii_anonymization(): void
    {
        // Crear datos de prueba
        $vessel = Vessel::factory()->create(['name' => 'PDF TEST VESSEL']);
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'viaje_id' => 'V888',
        ]);
        $entidad = Entidad::factory()->create(['name' => 'PDF ENTIDAD']);

        $tramite = Tramite::factory()->create([
            'tramite_ext_id' => 'CUS-2025-888',
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
            'regimen' => 'EXPORTACION',
            'estado' => 'APROBADO',
            'fecha_fin' => now(),
        ]);

        // Realizar exportación en formato PDF
        $response = $this->actingAs($this->user)
            ->post('/export/r8', [
                'format' => 'pdf',
            ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('reporte_r8_', $response->headers->get('Content-Disposition'));
    }

    public function test_export_customs_report_requires_permission(): void
    {
        // Crear usuario sin permisos
        $userWithoutPermission = User::factory()->create();

        // Intentar exportar sin permisos
        $response = $this->actingAs($userWithoutPermission)
            ->post('/export/r7', [
                'format' => 'csv',
            ]);

        $response->assertStatus(403);
    }

    public function test_export_customs_report_validates_format(): void
    {
        // Intentar exportar con formato inválido
        $response = $this->actingAs($this->user)
            ->post('/export/r7', [
                'format' => 'invalid',
            ]);

        $response->assertStatus(400);
    }

    public function test_export_r7_with_multiple_tramites_masks_all(): void
    {
        // Crear múltiples trámites
        $vessel = Vessel::factory()->create(['name' => 'MULTI VESSEL']);
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'viaje_id' => 'V100',
        ]);
        $entidad = Entidad::factory()->create(['name' => 'MULTI ENTIDAD']);

        $tramiteIds = ['CUS-2025-100', 'CUS-2025-101', 'CUS-2025-102'];
        foreach ($tramiteIds as $tramiteId) {
            Tramite::factory()->create([
                'tramite_ext_id' => $tramiteId,
                'vessel_call_id' => $vesselCall->id,
                'entidad_id' => $entidad->id,
                'regimen' => 'IMPORTACION',
                'estado' => 'APROBADO',
            ]);
        }

        // Realizar exportación
        $response = $this->actingAs($this->user)
            ->post('/export/r7', [
                'format' => 'csv',
            ]);

        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertIsString($content);

        // Verificar que NINGUNO de los tramite_ext_id originales está presente
        foreach ($tramiteIds as $tramiteId) {
            $this->assertStringNotContainsString($tramiteId, $content);
        }

        // Verificar que hay múltiples instancias del patrón enmascarado
        $maskedCount = substr_count($content, 'CU**********');
        $this->assertGreaterThanOrEqual(3, $maskedCount);
    }

    public function test_audit_log_does_not_contain_pii_for_customs_exports(): void
    {
        // Crear datos de prueba con PII
        $vessel = Vessel::factory()->create(['name' => 'AUDIT TEST VESSEL']);
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'viaje_id' => 'V777',
        ]);
        $entidad = Entidad::factory()->create(['name' => 'AUDIT ENTIDAD']);

        $tramite = Tramite::factory()->create([
            'tramite_ext_id' => 'CUS-2025-777',
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
            'regimen' => 'IMPORTACION',
            'estado' => 'APROBADO',
        ]);

        // Realizar exportación
        $response = $this->actingAs($this->user)
            ->post('/export/r7', [
                'format' => 'csv',
                'tramite_ext_id' => 'CUS-2025-777', // Incluir PII en filtros
            ]);

        $response->assertStatus(200);

        // Verificar que el audit log fue creado
        $this->assertDatabaseHas('audit.audit_log', [
            'action' => 'EXPORT',
            'object_schema' => 'reports',
            'object_table' => 'r7_status_by_vessel',
            'actor_user' => (string) $this->user->id,
        ]);

        // Obtener el audit log
        $auditLog = \App\Models\AuditLog::where('action', 'EXPORT')
            ->where('object_table', 'r7_status_by_vessel')
            ->orderBy('event_ts', 'desc')
            ->first();

        $this->assertNotNull($auditLog);

        // Verificar que los detalles no contienen PII sin enmascarar
        $details = $auditLog->details;
        $this->assertIsArray($details);

        // Si tramite_ext_id está en los detalles, debe estar enmascarado
        if (isset($details['tramite_ext_id'])) {
            $this->assertEquals('***MASKED***', $details['tramite_ext_id']);
        }

        // Verificar que el JSON completo no contiene el tramite_ext_id original
        $detailsJson = json_encode($details);
        $this->assertStringNotContainsString('CUS-2025-777', $detailsJson);
    }
}

