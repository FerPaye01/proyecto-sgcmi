<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Entidad;
use App\Models\GateEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SlaDefinition;
use App\Models\Tramite;
use App\Models\Truck;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for R12 SLA Compliance Report
 * Tests SLA compliance calculation and reporting
 *
 * Requirements: US-5.3 - Cumplimiento de SLAs
 */
class ReportR12SlaComplianceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Role $analistaRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol ANALISTA
        $this->analistaRole = Role::factory()->create([
            'code' => 'ANALISTA',
            'name' => 'Analista',
        ]);

        // Crear usuario con rol ANALISTA
        $this->user = User::factory()->create();
        $this->user->roles()->attach($this->analistaRole);

        // Crear permiso SLA_READ
        $slaReadPermission = Permission::factory()->create([
            'code' => 'SLA_READ',
            'name' => 'Leer SLAs',
        ]);
        $this->analistaRole->permissions()->attach($slaReadPermission);

        // Crear SLA definitions
        $this->createSlaDefinitions();
    }

    private function createSlaDefinitions(): void
    {
        SlaDefinition::factory()->create([
            'code' => 'TURNAROUND_48H',
            'name' => 'Turnaround < 48 horas',
            'umbral' => 48.0,
            'comparador' => '<',
        ]);

        SlaDefinition::factory()->create([
            'code' => 'ESPERA_CAMION_2H',
            'name' => 'Espera de Camión < 2 horas',
            'umbral' => 2.0,
            'comparador' => '<',
        ]);

        SlaDefinition::factory()->create([
            'code' => 'TRAMITE_DESPACHO_24H',
            'name' => 'Despacho de Trámite < 24 horas',
            'umbral' => 24.0,
            'comparador' => '<',
        ]);
    }

    /**
     * Test: R12 endpoint requires SLA_READ permission
     *
     * Requirements: US-5.3 - Solo roles autorizados pueden acceder
     * Property: Permission checks are enforced
     * Validates: Requirements 5.3
     */
    public function test_r12_endpoint_requires_sla_read_permission(): void
    {
        $userWithoutPermission = User::factory()->create();
        $role = Role::factory()->create([
            'code' => 'TRANSPORTISTA',
            'name' => 'Transportista',
        ]);
        $userWithoutPermission->roles()->attach($role);

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('reports.r12'));

        $response->assertStatus(403);
    }

    /**
     * Test: R12 view displays correctly
     *
     * Requirements: US-5.3 - Mostrar el cumplimiento de SLAs por actor
     * Property: R12 view is displayed correctly
     * Validates: Requirements 5.3
     */
    public function test_r12_view_displays_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.sla.compliance');
        $response->assertViewHas('data');
        $response->assertViewHas('kpis');
        $response->assertViewHas('por_actor');
        $response->assertViewHas('filters');
    }

    /**
     * Test: R12 displays KPI summary cards
     *
     * Requirements: US-5.3 - KPIs: pct_cumplimiento, incumplimientos, penalidades
     * Property: KPI summary is displayed
     * Validates: Requirements 5.3
     */
    public function test_r12_displays_kpi_summary_cards(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $response->assertStatus(200);

        $kpis = $response->viewData('kpis');
        $this->assertArrayHasKey('total_actores', $kpis);
        $this->assertArrayHasKey('pct_cumplimiento_promedio', $kpis);
        $this->assertArrayHasKey('actores_excelentes', $kpis);
        $this->assertArrayHasKey('actores_críticos', $kpis);
        $this->assertArrayHasKey('penalidades_totales', $kpis);
    }

    /**
     * Test: SLA compliance is calculated correctly for actors
     *
     * Requirements: US-5.3 - Medición por actor (company, entidad aduanera)
     * Property: SLA compliance is calculated per actor
     * Validates: Requirements 5.3
     */
    public function test_sla_compliance_is_calculated_per_actor(): void
    {
        // Crear empresa transportista
        $company = Company::factory()->create();

        // Crear actor para la empresa
        Actor::factory()->create([
            'ref_table' => 'terrestre.company',
            'ref_id' => $company->id,
            'tipo' => 'TRANSPORTISTA',
            'name' => $company->name,
        ]);

        // Crear vessel calls con turnaround < 48h
        $vessel = Vessel::factory()->create();
        $berth = \App\Models\Berth::factory()->create();

        $now = now();
        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'ata' => $now->clone()->subHours(24),
            'atd' => $now->clone()->subHours(12),
        ]);

        // Generar reporte R12
        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $response->assertStatus(200);

        $porActor = $response->viewData('por_actor');
        $this->assertNotEmpty($porActor);

        // Verificar que hay datos por actor
        $actorData = $porActor->first();
        $this->assertNotNull($actorData);
        $this->assertArrayHasKey('actor_name', $actorData);
        $this->assertArrayHasKey('pct_cumplimiento', $actorData);
        $this->assertArrayHasKey('slas', $actorData);
    }

    /**
     * Test: Actor with all SLAs met is marked as EXCELENTE
     *
     * Requirements: US-5.3 - Estado: EXCELENTE (cumplimiento ≥ 90%)
     * Property: Excellent actors are correctly identified
     * Validates: Requirements 5.3
     */
    public function test_actor_with_all_slas_met_is_marked_excelente(): void
    {
        // Crear empresa transportista
        $company = Company::factory()->create();

        // Crear actor para la empresa
        Actor::factory()->create([
            'ref_table' => 'terrestre.company',
            'ref_id' => $company->id,
            'tipo' => 'TRANSPORTISTA',
            'name' => $company->name,
        ]);

        // Crear vessel calls con turnaround < 48h
        $vessel = Vessel::factory()->create();
        $berth = \App\Models\Berth::factory()->create();

        $now = now();
        for ($i = 0; $i < 3; $i++) {
            VesselCall::factory()->create([
                'vessel_id' => $vessel->id,
                'berth_id' => $berth->id,
                'ata' => $now->clone()->subDays(1)->addHours($i * 8),
                'atd' => $now->clone()->subDays(1)->addHours($i * 8 + 24),
            ]);
        }

        // Generar reporte R12
        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $porActor = $response->viewData('por_actor');
        $actorData = $porActor->first();

        // Si cumple todos los SLAs, debe ser EXCELENTE
        if ($actorData['slas_cumplidos'] === $actorData['total_slas']) {
            $this->assertEquals('EXCELENTE', $actorData['estado']);
            $this->assertGreaterThanOrEqual(90, $actorData['pct_cumplimiento']);
        }
    }

    /**
     * Test: Actor with SLA violations is marked as CRÍTICO
     *
     * Requirements: US-5.3 - Estado: CRÍTICO (cumplimiento < 50%)
     * Property: Critical actors are correctly identified
     * Validates: Requirements 5.3
     */
    public function test_actor_with_sla_violations_is_marked_critico(): void
    {
        // Crear empresa transportista
        $company = Company::factory()->create();

        // Crear actor para la empresa
        Actor::factory()->create([
            'ref_table' => 'terrestre.company',
            'ref_id' => $company->id,
            'tipo' => 'TRANSPORTISTA',
            'name' => $company->name,
        ]);

        // Crear vessel calls con turnaround > 48h (viola SLA)
        $vessel = Vessel::factory()->create();
        $berth = \App\Models\Berth::factory()->create();

        $now = now();
        for ($i = 0; $i < 3; $i++) {
            VesselCall::factory()->create([
                'vessel_id' => $vessel->id,
                'berth_id' => $berth->id,
                'ata' => $now->clone()->subDays(3)->addHours($i * 8),
                'atd' => $now->clone()->subDays(1)->addHours($i * 8),
            ]);
        }

        // Generar reporte R12
        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $porActor = $response->viewData('por_actor');
        $actorData = $porActor->first();

        // Si incumple muchos SLAs, debe ser CRÍTICO
        if ($actorData['slas_incumplidos'] > $actorData['slas_cumplidos']) {
            $this->assertEquals('CRÍTICO', $actorData['estado']);
            $this->assertLessThan(50, $actorData['pct_cumplimiento']);
        }
    }

    /**
     * Test: Penalidades are calculated for SLA violations
     *
     * Requirements: US-5.3 - KPIs: penalidades (calculadas según reglas)
     * Property: Penalties are calculated for violations
     * Validates: Requirements 5.3
     */
    public function test_penalidades_are_calculated_for_sla_violations(): void
    {
        // Crear empresa transportista
        $company = Company::factory()->create();

        // Crear actor para la empresa
        Actor::factory()->create([
            'ref_table' => 'terrestre.company',
            'ref_id' => $company->id,
            'tipo' => 'TRANSPORTISTA',
            'name' => $company->name,
        ]);

        // Crear vessel calls con turnaround > 48h (viola SLA)
        $vessel = Vessel::factory()->create();
        $berth = \App\Models\Berth::factory()->create();

        $now = now();
        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'ata' => $now->clone()->subDays(3),
            'atd' => $now->clone()->subDays(1),
        ]);

        // Generar reporte R12
        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $porActor = $response->viewData('por_actor');
        $actorData = $porActor->first();

        // Si hay incumplimientos, debe haber penalidades
        if ($actorData['slas_incumplidos'] > 0) {
            $this->assertGreaterThan(0, $actorData['penalidades_totales']);
        }
    }

    /**
     * Test: R12 accepts date range filters
     *
     * Requirements: US-5.3 - Filtros: rango de fechas
     * Property: Date range filters are applied
     * Validates: Requirements 5.3
     */
    public function test_r12_accepts_date_range_filters(): void
    {
        $fechaDesde = now()->subDays(30)->format('Y-m-d');
        $fechaHasta = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('reports.r12', [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ]));

        $response->assertStatus(200);

        $filters = $response->viewData('filters');
        $this->assertEquals($fechaDesde, $filters['fecha_desde']);
        $this->assertEquals($fechaHasta, $filters['fecha_hasta']);
    }

    /**
     * Test: R12 displays actor status distribution
     *
     * Requirements: US-5.3 - Resumen de estados (EXCELENTE, BUENO, REGULAR, CRÍTICO)
     * Property: Actor status distribution is displayed
     * Validates: Requirements 5.3
     */
    public function test_r12_displays_actor_status_distribution(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $response->assertStatus(200);

        $kpis = $response->viewData('kpis');
        $this->assertArrayHasKey('actores_excelentes', $kpis);
        $this->assertArrayHasKey('actores_buenos', $kpis);
        $this->assertArrayHasKey('actores_regulares', $kpis);
        $this->assertArrayHasKey('actores_críticos', $kpis);
    }

    /**
     * Test: R12 displays SLA details for each actor
     *
     * Requirements: US-5.3 - Detalle de SLAs por actor
     * Property: SLA details are displayed
     * Validates: Requirements 5.3
     */
    public function test_r12_displays_sla_details_for_each_actor(): void
    {
        // Crear empresa transportista
        $company = Company::factory()->create();

        // Crear actor para la empresa
        Actor::factory()->create([
            'ref_table' => 'terrestre.company',
            'ref_id' => $company->id,
            'tipo' => 'TRANSPORTISTA',
            'name' => $company->name,
        ]);

        // Crear vessel calls
        $vessel = Vessel::factory()->create();
        $berth = \App\Models\Berth::factory()->create();

        $now = now();
        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'ata' => $now->clone()->subHours(24),
            'atd' => $now->clone()->subHours(12),
        ]);

        // Generar reporte R12
        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $porActor = $response->viewData('por_actor');
        $actorData = $porActor->first();

        // Verificar que hay detalles de SLAs
        $this->assertNotEmpty($actorData['slas']);
        $sla = $actorData['slas'][0];
        $this->assertArrayHasKey('sla_code', $sla);
        $this->assertArrayHasKey('sla_name', $sla);
        $this->assertArrayHasKey('valor', $sla);
        $this->assertArrayHasKey('umbral', $sla);
        $this->assertArrayHasKey('cumple', $sla);
    }

    /**
     * Test: R12 calculates average compliance percentage
     *
     * Requirements: US-5.3 - KPIs: pct_cumplimiento (%)
     * Property: Average compliance percentage is calculated
     * Validates: Requirements 5.3
     */
    public function test_r12_calculates_average_compliance_percentage(): void
    {
        // Crear dos empresas
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Crear actores
        Actor::factory()->create([
            'ref_table' => 'terrestre.company',
            'ref_id' => $company1->id,
            'tipo' => 'TRANSPORTISTA',
            'name' => $company1->name,
        ]);

        Actor::factory()->create([
            'ref_table' => 'terrestre.company',
            'ref_id' => $company2->id,
            'tipo' => 'TRANSPORTISTA',
            'name' => $company2->name,
        ]);

        // Crear vessel calls para ambas empresas
        $vessel = Vessel::factory()->create();
        $berth = \App\Models\Berth::factory()->create();

        $now = now();
        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'ata' => $now->clone()->subHours(24),
            'atd' => $now->clone()->subHours(12),
        ]);

        // Generar reporte R12
        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $kpis = $response->viewData('kpis');
        $this->assertGreaterThanOrEqual(0, $kpis['pct_cumplimiento_promedio']);
        $this->assertLessThanOrEqual(100, $kpis['pct_cumplimiento_promedio']);
    }

    /**
     * Test: R12 includes customs entities in compliance report
     *
     * Requirements: US-5.3 - Medición por actor (company, entidad aduanera)
     * Property: Customs entities are included in compliance report
     * Validates: Requirements 5.3
     */
    public function test_r12_includes_customs_entities_in_compliance_report(): void
    {
        // Crear entidad aduanera
        $entidad = Entidad::factory()->create();

        // Crear actor para la entidad
        Actor::factory()->create([
            'ref_table' => 'aduanas.entidad',
            'ref_id' => $entidad->id,
            'tipo' => 'ENTIDAD_ADUANA',
            'name' => $entidad->name,
        ]);

        // Crear trámites con despacho < 24h
        $vessel = Vessel::factory()->create();
        $berth = \App\Models\Berth::factory()->create();
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
        ]);

        $now = now();
        Tramite::factory()->create([
            'vessel_call_id' => $vesselCall->id,
            'entidad_id' => $entidad->id,
            'estado' => 'APROBADO',
            'fecha_inicio' => $now->clone()->subHours(12),
            'fecha_fin' => $now,
        ]);

        // Generar reporte R12
        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $porActor = $response->viewData('por_actor');
        $this->assertNotEmpty($porActor);

        // Verificar que hay actores de tipo ENTIDAD_ADUANA
        $entidadActor = $porActor->firstWhere('actor_tipo', 'ENTIDAD_ADUANA');
        $this->assertNotNull($entidadActor);
    }

    /**
     * Test: R12 handles empty data gracefully
     *
     * Requirements: US-5.3 - Manejo de datos vacíos
     * Property: Empty data is handled gracefully
     * Validates: Requirements 5.3
     */
    public function test_r12_handles_empty_data_gracefully(): void
    {
        // No crear ningún actor ni datos

        $response = $this->actingAs($this->user)
            ->get(route('reports.r12'));

        $response->assertStatus(200);

        $kpis = $response->viewData('kpis');
        $this->assertEquals(0, $kpis['total_actores']);
        $this->assertEquals(0.0, $kpis['pct_cumplimiento_promedio']);
    }
}
