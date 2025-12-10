<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Berth;
use App\Models\Company;
use App\Models\GateEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tramite;
use App\Models\Truck;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for R10 KPI Panel API endpoint (polling)
 * Tests the API endpoint that returns JSON data for Alpine.js polling
 *
 * Requirements: US-5.1 - Panel de KPIs Ejecutivo con actualización automática
 */
class ReportR10KpiPollingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        $directivoRole = Role::factory()->create(['code' => 'DIRECTIVO', 'name' => 'Directivo']);

        // Crear usuario con rol DIRECTIVO
        $this->user = User::factory()->create();
        $this->user->roles()->attach($directivoRole);

        // Crear permisos
        $kpiReadPermission = Permission::factory()->create(['code' => 'KPI_READ', 'name' => 'Leer KPIs']);
        $directivoRole->permissions()->attach($kpiReadPermission);
    }

    /**
     * Test: R10 API endpoint returns JSON with KPI data
     *
     * Requirements: US-5.1 - API endpoint para polling debe retornar JSON
     */
    public function test_r10_api_endpoint_returns_json_with_kpi_data(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r10.api'));

        $response->assertStatus(200);
        $response->assertJson([
            'kpis' => [],
            'periodo_actual' => [],
            'periodo_anterior' => [],
        ]);
        $response->assertJsonStructure([
            'kpis' => [
                'turnaround' => [
                    'valor_actual',
                    'valor_anterior',
                    'meta',
                    'diferencia',
                    'pct_cambio',
                    'tendencia',
                    'tendencia_positiva',
                    'cumple_meta',
                ],
                'espera_camion' => [
                    'valor_actual',
                    'valor_anterior',
                    'meta',
                    'diferencia',
                    'pct_cambio',
                    'tendencia',
                    'tendencia_positiva',
                    'cumple_meta',
                ],
                'cumpl_citas' => [
                    'valor_actual',
                    'valor_anterior',
                    'meta',
                    'diferencia',
                    'pct_cambio',
                    'tendencia',
                    'tendencia_positiva',
                    'cumple_meta',
                ],
                'tramites_ok' => [
                    'valor_actual',
                    'valor_anterior',
                    'meta',
                    'diferencia',
                    'pct_cambio',
                    'tendencia',
                    'tendencia_positiva',
                    'cumple_meta',
                ],
            ],
            'periodo_actual' => [
                'fecha_desde',
                'fecha_hasta',
            ],
            'periodo_anterior' => [
                'fecha_desde',
                'fecha_hasta',
            ],
            'timestamp',
        ]);
    }

    /**
     * Test: R10 API endpoint requires KPI_READ permission
     *
     * Requirements: US-5.1 - API endpoint debe validar permisos
     */
    public function test_r10_api_endpoint_requires_kpi_read_permission(): void
    {
        $userWithoutPermission = User::factory()->create();
        $role = Role::factory()->create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        $userWithoutPermission->roles()->attach($role);

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('reports.r10.api'));

        $response->assertStatus(403);
    }

    /**
     * Test: R10 API endpoint accepts date filters
     *
     * Requirements: US-5.1 - API endpoint debe aceptar filtros de fecha
     */
    public function test_r10_api_endpoint_accepts_date_filters(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r10.api', [
                'fecha_desde' => '2025-01-01',
                'fecha_hasta' => '2025-01-31',
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'kpis' => [],
        ]);
    }

    /**
     * Test: R10 API endpoint returns timestamp
     *
     * Requirements: US-5.1 - API endpoint debe incluir timestamp de actualización
     */
    public function test_r10_api_endpoint_returns_timestamp(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r10.api'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'timestamp',
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['timestamp']);
        // Verify it's a valid ISO 8601 timestamp
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $data['timestamp']
        );
    }

    /**
     * Test: R10 API endpoint returns updated KPI values
     *
     * Requirements: US-5.1 - API endpoint debe retornar valores actualizados
     */
    public function test_r10_api_endpoint_returns_updated_kpi_values(): void
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
            ->get(route('reports.r10.api'));

        $response->assertStatus(200);

        $data = $response->json();
        $kpis = $data['kpis'];

        // Verify KPI values are numeric
        $this->assertIsNumeric($kpis['turnaround']['valor_actual']);
        $this->assertIsNumeric($kpis['espera_camion']['valor_actual']);
        $this->assertIsNumeric($kpis['cumpl_citas']['valor_actual']);
        $this->assertIsNumeric($kpis['tramites_ok']['valor_actual']);
    }

    /**
     * Test: R10 API endpoint returns correct content type
     *
     * Requirements: US-5.1 - API endpoint debe retornar JSON
     */
    public function test_r10_api_endpoint_returns_json_content_type(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r10.api'));

        $response->assertStatus(200);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }
}
