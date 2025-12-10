<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Berth;
use App\Models\Company;
use App\Models\GateEvent;
use App\Models\Role;
use App\Models\Tramite;
use App\Models\Truck;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for R10 KPI Panel endpoint
 * Tests the controller method and view rendering
 *
 * Requirements: US-5.1 - Panel de KPIs Ejecutivo
 */
class ReportR10KpiPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        $adminRole = Role::factory()->create(['code' => 'ADMIN', 'name' => 'Administrador']);
        $analystaRole = Role::factory()->create(['code' => 'ANALISTA', 'name' => 'Analista']);
        $directivoRole = Role::factory()->create(['code' => 'DIRECTIVO', 'name' => 'Directivo']);

        // Crear usuario con rol DIRECTIVO
        $this->user = User::factory()->create();
        $this->user->roles()->attach($directivoRole);

        // Crear permisos
        $kpiReadPermission = \App\Models\Permission::factory()->create(['code' => 'KPI_READ', 'name' => 'Leer KPIs']);
        $directivoRole->permissions()->attach($kpiReadPermission);
    }

    /**
     * Test: R10 endpoint returns 200 and renders view
     *
     * Requirements: US-5.1 - Panel de KPIs Ejecutivo debe ser accesible
     */
    public function test_r10_endpoint_returns_200_and_renders_view(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r10'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.kpi.panel');
    }

    /**
     * Test: R10 endpoint requires KPI_READ permission
     *
     * Requirements: US-5.1 - Solo roles autorizados pueden acceder
     */
    public function test_r10_endpoint_requires_kpi_read_permission(): void
    {
        $userWithoutPermission = User::factory()->create();
        $role = Role::factory()->create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        $userWithoutPermission->roles()->attach($role);

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('reports.r10'));

        $response->assertStatus(403);
    }

    /**
     * Test: R10 view contains KPI data
     *
     * Requirements: US-5.1 - Panel debe mostrar KPIs consolidados
     */
    public function test_r10_view_contains_kpi_data(): void
    {
        // Crear datos de prueba
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();
        $company = Company::factory()->create();
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        // Crear vessel call completada
        $vesselCall = VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'ata' => now()->subDays(5),
            'atd' => now()->subDays(5)->addHours(12),
        ]);

        // Crear appointment
        $appointment = Appointment::factory()->create([
            'truck_id' => $truck->id,
            'company_id' => $company->id,
            'vessel_call_id' => $vesselCall->id,
            'hora_llegada' => now()->subDays(5),
            'estado' => 'ATENDIDA',
        ]);

        // Crear gate event
        GateEvent::factory()->create([
            'truck_id' => $truck->id,
            'cita_id' => $appointment->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->subDays(5)->addHours(1),
        ]);

        // Crear trámite aprobado
        Tramite::factory()->create([
            'vessel_call_id' => $vesselCall->id,
            'estado' => 'APROBADO',
            'fecha_inicio' => now()->subDays(5),
            'fecha_fin' => now()->subDays(5)->addHours(8),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.r10'));

        $response->assertStatus(200);
        $response->assertViewHas('kpis');
        $response->assertViewHas('periodo_actual');
        $response->assertViewHas('periodo_anterior');

        // Verificar que los KPIs están presentes
        $kpis = $response->viewData('kpis');
        $this->assertArrayHasKey('turnaround', $kpis);
        $this->assertArrayHasKey('espera_camion', $kpis);
        $this->assertArrayHasKey('cumpl_citas', $kpis);
        $this->assertArrayHasKey('tramites_ok', $kpis);
    }

    /**
     * Test: R10 view displays KPI values correctly
     *
     * Requirements: US-5.1 - Visualización con valor actual, meta y tendencia
     */
    public function test_r10_view_displays_kpi_values_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r10'));

        $response->assertStatus(200);

        $kpis = $response->viewData('kpis');

        // Verificar estructura de cada KPI
        foreach (['turnaround', 'espera_camion', 'cumpl_citas', 'tramites_ok'] as $kpi) {
            $this->assertArrayHasKey('valor_actual', $kpis[$kpi]);
            $this->assertArrayHasKey('valor_anterior', $kpis[$kpi]);
            $this->assertArrayHasKey('meta', $kpis[$kpi]);
            $this->assertArrayHasKey('diferencia', $kpis[$kpi]);
            $this->assertArrayHasKey('pct_cambio', $kpis[$kpi]);
            $this->assertArrayHasKey('tendencia', $kpis[$kpi]);
            $this->assertArrayHasKey('tendencia_positiva', $kpis[$kpi]);
            $this->assertArrayHasKey('cumple_meta', $kpis[$kpi]);
        }
    }

    /**
     * Test: R10 accepts date filters
     *
     * Requirements: US-5.1 - Filtros por fecha
     */
    public function test_r10_accepts_date_filters(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r10', [
                'fecha_desde' => '2025-01-01',
                'fecha_hasta' => '2025-01-31',
            ]));

        $response->assertStatus(200);
        $response->assertViewHas('filters');

        $filters = $response->viewData('filters');
        $this->assertEquals('2025-01-01', $filters['fecha_desde']);
        $this->assertEquals('2025-01-31', $filters['fecha_hasta']);
    }

    /**
     * Test: R10 accepts meta value filters
     *
     * Requirements: US-5.1 - Configuración de metas
     */
    public function test_r10_accepts_meta_value_filters(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r10', [
                'meta_turnaround' => 50.0,
                'meta_espera_camion' => 3.0,
                'meta_cumpl_citas' => 90.0,
                'meta_tramites_ok' => 95.0,
            ]));

        $response->assertStatus(200);

        $kpis = $response->viewData('kpis');
        $this->assertEquals(50.0, $kpis['turnaround']['meta']);
        $this->assertEquals(3.0, $kpis['espera_camion']['meta']);
        $this->assertEquals(90.0, $kpis['cumpl_citas']['meta']);
        $this->assertEquals(95.0, $kpis['tramites_ok']['meta']);
    }

    /**
     * Test: R10 view renders KPI cards with correct styling
     *
     * Requirements: US-5.1 - Visualización: tarjetas con valor actual, meta y tendencia
     */
    public function test_r10_view_renders_kpi_cards(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r10'));

        $response->assertStatus(200);
        $response->assertSee('Turnaround Promedio');
        $response->assertSee('Espera de Camión');
        $response->assertSee('Cumplimiento de Citas');
        $response->assertSee('Trámites Aprobados');
    }

    /**
     * Test: R10 view displays period information
     *
     * Requirements: US-5.1 - Comparativa: periodo actual vs periodo anterior
     */
    public function test_r10_view_displays_period_information(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r10'));

        $response->assertStatus(200);
        $response->assertSee('Período Actual');
        $response->assertSee('Período Anterior');
    }

    /**
     * Test: Solo DIRECTIVO puede acceder al panel ejecutivo
     *
     * Requirements: US-5.1 - Roles autorizados: DIRECTIVO, ANALISTA, ADMIN, AUDITOR
     * Verifies that only authorized roles can access the KPI panel
     */
    public function test_only_authorized_roles_can_access_kpi_panel(): void
    {
        // Create roles
        $adminRole = Role::factory()->create(['code' => 'ADMIN', 'name' => 'Administrador']);
        $analystaRole = Role::factory()->create(['code' => 'ANALISTA', 'name' => 'Analista']);
        $auditorRole = Role::factory()->create(['code' => 'AUDITOR', 'name' => 'Auditor']);
        $directivoRole = Role::factory()->create(['code' => 'DIRECTIVO', 'name' => 'Directivo']);
        $transportistaRole = Role::factory()->create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        $operadorGatesRole = Role::factory()->create(['code' => 'OPERADOR_GATES', 'name' => 'Operador de Gates']);

        // Create permission
        $kpiReadPermission = \App\Models\Permission::factory()->create(['code' => 'KPI_READ', 'name' => 'Leer KPIs']);

        // Attach permission to authorized roles
        $adminRole->permissions()->attach($kpiReadPermission);
        $analystaRole->permissions()->attach($kpiReadPermission);
        $auditorRole->permissions()->attach($kpiReadPermission);
        $directivoRole->permissions()->attach($kpiReadPermission);

        // Test: DIRECTIVO can access
        $directivoUser = User::factory()->create();
        $directivoUser->roles()->attach($directivoRole);
        $response = $this->actingAs($directivoUser)->get(route('reports.r10'));
        $response->assertStatus(200);

        // Test: ANALISTA can access
        $analystaUser = User::factory()->create();
        $analystaUser->roles()->attach($analystaRole);
        $response = $this->actingAs($analystaUser)->get(route('reports.r10'));
        $response->assertStatus(200);

        // Test: ADMIN can access
        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);
        $response = $this->actingAs($adminUser)->get(route('reports.r10'));
        $response->assertStatus(200);

        // Test: AUDITOR can access
        $auditorUser = User::factory()->create();
        $auditorUser->roles()->attach($auditorRole);
        $response = $this->actingAs($auditorUser)->get(route('reports.r10'));
        $response->assertStatus(200);

        // Test: TRANSPORTISTA cannot access
        $transportistaUser = User::factory()->create();
        $transportistaUser->roles()->attach($transportistaRole);
        $response = $this->actingAs($transportistaUser)->get(route('reports.r10'));
        $response->assertStatus(403);

        // Test: OPERADOR_GATES cannot access
        $operadorUser = User::factory()->create();
        $operadorUser->roles()->attach($operadorGatesRole);
        $response = $this->actingAs($operadorUser)->get(route('reports.r10'));
        $response->assertStatus(403);

        // Test: Unauthenticated user cannot access
        $response = $this->get(route('reports.r10'));
        $response->assertRedirect('/login');
    }
}
