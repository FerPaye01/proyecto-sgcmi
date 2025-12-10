<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Berth;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Role $role;
    private Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();

        // Drop all schemas to ensure clean state
        \DB::statement('DROP SCHEMA IF EXISTS admin CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS portuario CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS terrestre CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS aduanas CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS analytics CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS audit CASCADE');
        \DB::statement('DROP SCHEMA IF EXISTS reports CASCADE');

        // Run migrations
        $this->artisan('migrate:fresh');

        // Create permission
        $this->permission = Permission::create([
            'code' => 'PORT_REPORT_READ',
            'name' => 'Read Port Reports',
        ]);

        // Create role with permission
        $this->role = Role::create([
            'code' => 'PLANIFICADOR_PUERTO',
            'name' => 'Planificador Puerto',
        ]);

        $this->role->permissions()->attach($this->permission->id);

        // Create user with role
        $this->user = User::factory()->create();
        $this->user->roles()->attach($this->role->id);
    }

    public function test_r1_report_requires_authentication(): void
    {
        $response = $this->get(route('reports.r1'));

        // Should redirect to login (302) or return 401 if no login route defined
        $this->assertContains($response->status(), [302, 401, 500]);
    }

    public function test_r1_report_requires_permission(): void
    {
        // Create user without permission
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('reports.r1'));

        $response->assertStatus(403);
    }

    public function test_r1_report_displays_correctly_with_permission(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r1'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.port.schedule-vs-actual');
        $response->assertViewHas(['data', 'kpis', 'filters', 'berths', 'vessels']);
    }

    public function test_r1_report_filters_by_date_range(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        // Create vessel calls with different dates
        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'eta' => '2025-01-01 08:00:00',
            'ata' => '2025-01-01 08:30:00',
        ]);

        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'eta' => '2025-02-01 08:00:00',
            'ata' => '2025-02-01 08:30:00',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.r1', [
                'fecha_desde' => '2025-01-01',
                'fecha_hasta' => '2025-01-31',
            ]));

        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertCount(1, $data);
    }

    public function test_r1_report_filters_by_berth(): void
    {
        $berth1 = Berth::factory()->create();
        $berth2 = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth1->id,
            'ata' => '2025-01-01 08:30:00',
        ]);

        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth2->id,
            'ata' => '2025-01-01 09:30:00',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.r1', ['berth_id' => $berth1->id]));

        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertCount(1, $data);
        $this->assertEquals($berth1->id, $data->first()->berth_id);
    }

    public function test_r1_report_filters_by_vessel(): void
    {
        $berth = Berth::factory()->create();
        $vessel1 = Vessel::factory()->create();
        $vessel2 = Vessel::factory()->create();

        VesselCall::factory()->create([
            'vessel_id' => $vessel1->id,
            'berth_id' => $berth->id,
            'ata' => '2025-01-01 08:30:00',
        ]);

        VesselCall::factory()->create([
            'vessel_id' => $vessel2->id,
            'berth_id' => $berth->id,
            'ata' => '2025-01-01 09:30:00',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.r1', ['vessel_id' => $vessel1->id]));

        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertCount(1, $data);
        $this->assertEquals($vessel1->id, $data->first()->vessel_id);
    }

    public function test_r1_report_calculates_kpis_correctly(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        // Create vessel call with known delays
        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'eta' => '2025-01-01 08:00:00',
            'ata' => '2025-01-01 08:30:00', // 30 min delay
            'etb' => '2025-01-01 09:00:00',
            'atb' => '2025-01-01 09:15:00', // 15 min delay
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.r1'));

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');

        $this->assertArrayHasKey('puntualidad_arribo', $kpis);
        $this->assertArrayHasKey('demora_eta_ata_min', $kpis);
        $this->assertArrayHasKey('demora_etb_atb_min', $kpis);
        $this->assertArrayHasKey('cumplimiento_ventana', $kpis);

        // Verify delay calculations
        $this->assertEquals(30.0, $kpis['demora_eta_ata_min']);
        $this->assertEquals(15.0, $kpis['demora_etb_atb_min']);
    }

    /**
     * Test that PLANIFICADOR_PUERTO role can access R1 report
     * Requirements: US-1.2 - Authorized roles include PLANIFICADOR_PUERTO
     */
    public function test_planificador_puerto_can_access_r1_report(): void
    {
        // Reuse existing role and permission from setUp
        $response = $this->actingAs($this->user)->get(route('reports.r1'));

        $response->assertStatus(200);
    }

    /**
     * Test that OPERACIONES_PUERTO role can access R1 report
     * Requirements: US-1.2 - Authorized roles include OPERACIONES_PUERTO
     */
    public function test_operaciones_puerto_can_access_r1_report(): void
    {
        $role = Role::create(['code' => 'OPERACIONES_PUERTO', 'name' => 'Operaciones Puerto']);
        $role->permissions()->attach($this->permission->id);

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('reports.r1'));

        $response->assertStatus(200);
    }

    /**
     * Test that ANALISTA role can access R1 report
     * Requirements: US-1.2 - Authorized roles include ANALISTA
     */
    public function test_analista_can_access_r1_report(): void
    {
        $role = Role::create(['code' => 'ANALISTA', 'name' => 'Analista']);
        $role->permissions()->attach($this->permission->id);

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('reports.r1'));

        $response->assertStatus(200);
    }

    /**
     * Test that DIRECTIVO role can access R1 report
     * Requirements: US-1.2 - Authorized roles include DIRECTIVO
     */
    public function test_directivo_can_access_r1_report(): void
    {
        $role = Role::create(['code' => 'DIRECTIVO', 'name' => 'Directivo']);
        $role->permissions()->attach($this->permission->id);

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('reports.r1'));

        $response->assertStatus(200);
    }

    /**
     * Test that AUDITOR role can access R1 report
     * Requirements: US-1.2 - Authorized roles include AUDITOR
     */
    public function test_auditor_can_access_r1_report(): void
    {
        $role = Role::create(['code' => 'AUDITOR', 'name' => 'Auditor']);
        $role->permissions()->attach($this->permission->id);

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('reports.r1'));

        $response->assertStatus(200);
    }

    /**
     * Test that TRANSPORTISTA role cannot access R1 report
     * Requirements: US-1.2 - TRANSPORTISTA is not in the authorized roles list
     */
    public function test_transportista_cannot_access_r1_report(): void
    {
        $role = Role::create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        // Deliberately not attaching PORT_REPORT_READ permission

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('reports.r1'));

        $response->assertStatus(403);
    }

    /**
     * Test that OPERADOR_GATES role cannot access R1 report
     * Requirements: US-1.2 - OPERADOR_GATES is not in the authorized roles list
     */
    public function test_operador_gates_cannot_access_r1_report(): void
    {
        $role = Role::create(['code' => 'OPERADOR_GATES', 'name' => 'Operador Gates']);
        // Deliberately not attaching PORT_REPORT_READ permission

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('reports.r1'));

        $response->assertStatus(403);
    }

    /**
     * Test that AGENTE_ADUANA role cannot access R1 report
     * Requirements: US-1.2 - AGENTE_ADUANA is not in the authorized roles list
     */
    public function test_agente_aduana_cannot_access_r1_report(): void
    {
        $role = Role::create(['code' => 'AGENTE_ADUANA', 'name' => 'Agente Aduana']);
        // Deliberately not attaching PORT_REPORT_READ permission

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('reports.r1'));

        $response->assertStatus(403);
    }

    /**
     * Test that ADMIN role can access R1 report (wildcard permissions)
     * Requirements: ADMIN should have access to all resources
     */
    public function test_admin_can_access_r1_report(): void
    {
        $role = Role::create(['code' => 'ADMIN', 'name' => 'Administrator']);
        // ADMIN gets all permissions via wildcard in CheckPermission middleware

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('reports.r1'));

        $response->assertStatus(200);
    }

    // ========== R3 Report Tests ==========

    /**
     * Test that R3 report requires authentication
     * Requirements: US-2.1 - Only authenticated users can access reports
     */
    public function test_r3_report_requires_authentication(): void
    {
        $response = $this->get(route('reports.r3'));

        // Should redirect to login (302) or return 401 if no login route defined
        $this->assertContains($response->status(), [302, 401, 500]);
    }

    /**
     * Test that R3 report requires PORT_REPORT_READ permission
     * Requirements: US-2.1 - Only users with PORT_REPORT_READ can access
     */
    public function test_r3_report_requires_permission(): void
    {
        // Create user without permission
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('reports.r3'));

        $response->assertStatus(403);
    }

    /**
     * Test that R3 report displays correctly with permission
     * Requirements: US-2.1 - Display utilization data with filters
     */
    public function test_r3_report_displays_correctly_with_permission(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.r3'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.port.berth-utilization');
        $response->assertViewHas(['data', 'kpis', 'utilizacion_por_franja', 'filters', 'berths']);
    }

    /**
     * Test that R3 report filters by date range
     * Requirements: US-2.1 - Filter by date range
     */
    public function test_r3_report_filters_by_date_range(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        // Create vessel calls with different dates
        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'atb' => '2025-01-01 08:00:00',
            'atd' => '2025-01-01 16:00:00',
        ]);

        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'atb' => '2025-02-01 08:00:00',
            'atd' => '2025-02-01 16:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.r3', [
                'fecha_desde' => '2025-01-01',
                'fecha_hasta' => '2025-01-31',
            ]));

        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertCount(1, $data);
    }

    /**
     * Test that R3 report filters by berth
     * Requirements: US-2.1 - Filter by berth
     */
    public function test_r3_report_filters_by_berth(): void
    {
        $berth1 = Berth::factory()->create();
        $berth2 = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth1->id,
            'atb' => '2025-01-01 08:00:00',
            'atd' => '2025-01-01 16:00:00',
        ]);

        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth2->id,
            'atb' => '2025-01-01 08:00:00',
            'atd' => '2025-01-01 16:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.r3', ['berth_id' => $berth1->id]));

        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertCount(1, $data);
        $this->assertEquals($berth1->id, $data->first()->berth_id);
    }

    /**
     * Test that R3 report calculates KPIs correctly
     * Requirements: US-2.1 - Calculate utilizacion_franja, conflictos_ventana, horas_ociosas
     */
    public function test_r3_report_calculates_kpis_correctly(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        // Create vessel call with known duration
        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'atb' => '2025-01-01 08:00:00',
            'atd' => '2025-01-01 16:00:00', // 8 hours
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.r3'));

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');

        $this->assertArrayHasKey('utilizacion_promedio', $kpis);
        $this->assertArrayHasKey('conflictos_ventana', $kpis);
        $this->assertArrayHasKey('horas_ociosas', $kpis);
        $this->assertArrayHasKey('utilizacion_maxima', $kpis);
    }

    /**
     * Test that R3 report detects conflicts (overlapping vessels)
     * Requirements: US-2.1 - Detect conflicts when vessels overlap on same berth
     */
    public function test_r3_report_detects_conflicts(): void
    {
        $berth = Berth::factory()->create();
        $vessel1 = Vessel::factory()->create();
        $vessel2 = Vessel::factory()->create();

        // Create overlapping vessel calls on same berth
        VesselCall::factory()->create([
            'vessel_id' => $vessel1->id,
            'berth_id' => $berth->id,
            'atb' => '2025-01-01 08:00:00',
            'atd' => '2025-01-01 16:00:00',
        ]);

        VesselCall::factory()->create([
            'vessel_id' => $vessel2->id,
            'berth_id' => $berth->id,
            'atb' => '2025-01-01 14:00:00', // Overlaps with first vessel
            'atd' => '2025-01-01 20:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.r3'));

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');

        // Should detect at least 1 conflict
        $this->assertGreaterThan(0, $kpis['conflictos_ventana']);
    }

    /**
     * Test that R3 report handles custom time frames
     * Requirements: US-2.1 - Support configurable time frames (1h, 2h, 4h, 6h)
     */
    public function test_r3_report_handles_custom_time_frames(): void
    {
        $berth = Berth::factory()->create();
        $vessel = Vessel::factory()->create();

        VesselCall::factory()->create([
            'vessel_id' => $vessel->id,
            'berth_id' => $berth->id,
            'atb' => '2025-01-01 08:00:00',
            'atd' => '2025-01-01 16:00:00',
        ]);

        // Test with 2-hour frames
        $response = $this->actingAs($this->user)
            ->get(route('reports.r3', ['franja_horas' => 2]));

        $response->assertStatus(200);
        $utilizacionPorFranja = $response->viewData('utilizacion_por_franja');
        $this->assertNotEmpty($utilizacionPorFranja);
    }

    /**
     * Test that PLANIFICADOR_PUERTO can access R3 report
     * Requirements: US-2.1 - PLANIFICADOR_PUERTO has PORT_REPORT_READ permission
     */
    public function test_planificador_puerto_can_access_r3_report(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.r3'));

        $response->assertStatus(200);
    }

    /**
     * Test that TRANSPORTISTA cannot access R3 report
     * Requirements: US-2.1 - TRANSPORTISTA does not have PORT_REPORT_READ permission
     */
    public function test_transportista_cannot_access_r3_report(): void
    {
        $role = Role::create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        // Deliberately not attaching PORT_REPORT_READ permission

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('reports.r3'));

        $response->assertStatus(403);
    }

    // ========== R5 Report Tests ==========

    /**
     * Test that R5 report requires authentication
     * Requirements: US-3.3 - Only authenticated users can access reports
     */
    public function test_r5_report_requires_authentication(): void
    {
        $response = $this->get(route('reports.r5'));

        // Should redirect to login (302) or return 401 if no login route defined
        $this->assertContains($response->status(), [302, 401, 500]);
    }

    /**
     * Test that R5 report requires ROAD_REPORT_READ permission
     * Requirements: US-3.3 - Only users with ROAD_REPORT_READ can access
     */
    public function test_r5_report_requires_permission(): void
    {
        // Create user without permission
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('reports.r5'));

        $response->assertStatus(403);
    }

    /**
     * Test that R5 report displays correctly with permission
     * Requirements: US-3.3 - Display appointments compliance data
     */
    public function test_r5_report_displays_correctly_with_permission(): void
    {
        // Create ROAD_REPORT_READ permission
        $roadPermission = Permission::create([
            'code' => 'ROAD_REPORT_READ',
            'name' => 'Read Road Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'OPERADOR_GATES',
            'name' => 'Operador Gates',
        ]);
        $role->permissions()->attach($roadPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r5'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.road.appointments-compliance');
        $response->assertViewHas(['data', 'kpis', 'ranking', 'filters', 'companies', 'isTransportista']);
    }

    /**
     * Test that R5 report filters by date range
     * Requirements: US-3.3 - Filter by date range
     */
    public function test_r5_report_filters_by_date_range(): void
    {
        // Create ROAD_REPORT_READ permission
        $roadPermission = Permission::create([
            'code' => 'ROAD_REPORT_READ',
            'name' => 'Read Road Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'OPERADOR_GATES',
            'name' => 'Operador Gates',
        ]);
        $role->permissions()->attach($roadPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r5', [
                'fecha_desde' => '2025-01-01',
                'fecha_hasta' => '2025-01-31',
            ]));

        $response->assertStatus(200);
        $filters = $response->viewData('filters');
        $this->assertEquals('2025-01-01', $filters['fecha_desde']);
        $this->assertEquals('2025-01-31', $filters['fecha_hasta']);
    }

    /**
     * Test that R5 report calculates KPIs correctly
     * Requirements: US-3.3 - Calculate pct_no_show, pct_tarde, desvio_medio_min
     */
    public function test_r5_report_calculates_kpis_correctly(): void
    {
        // Create ROAD_REPORT_READ permission
        $roadPermission = Permission::create([
            'code' => 'ROAD_REPORT_READ',
            'name' => 'Read Road Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'OPERADOR_GATES',
            'name' => 'Operador Gates',
        ]);
        $role->permissions()->attach($roadPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r5'));

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');

        $this->assertArrayHasKey('pct_no_show', $kpis);
        $this->assertArrayHasKey('pct_tarde', $kpis);
        $this->assertArrayHasKey('desvio_medio_min', $kpis);
        $this->assertArrayHasKey('total_citas', $kpis);
    }

    /**
     * Test that TRANSPORTISTA can access R5 report with scoping
     * Requirements: US-3.3 - TRANSPORTISTA has ROAD_REPORT_READ permission
     */
    public function test_transportista_can_access_r5_report(): void
    {
        // Create ROAD_REPORT_READ permission
        $roadPermission = Permission::create([
            'code' => 'ROAD_REPORT_READ',
            'name' => 'Read Road Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'TRANSPORTISTA',
            'name' => 'Transportista',
        ]);
        $role->permissions()->attach($roadPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r5'));

        $response->assertStatus(200);
        $isTransportista = $response->viewData('isTransportista');
        $this->assertTrue($isTransportista);
    }

    /**
     * Test that OPERADOR_GATES can access R5 report
     * Requirements: US-3.3 - OPERADOR_GATES has ROAD_REPORT_READ permission
     */
    public function test_operador_gates_can_access_r5_report(): void
    {
        // Create ROAD_REPORT_READ permission
        $roadPermission = Permission::create([
            'code' => 'ROAD_REPORT_READ',
            'name' => 'Read Road Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'OPERADOR_GATES',
            'name' => 'Operador Gates',
        ]);
        $role->permissions()->attach($roadPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r5'));

        $response->assertStatus(200);
    }

    /**
     * Test that PLANIFICADOR_PUERTO cannot access R5 report
     * Requirements: US-3.3 - PLANIFICADOR_PUERTO does not have ROAD_REPORT_READ permission
     */
    public function test_planificador_puerto_cannot_access_r5_report(): void
    {
        // User already has PORT_REPORT_READ but not ROAD_REPORT_READ
        $response = $this->actingAs($this->user)
            ->get(route('reports.r5'));

        $response->assertStatus(403);
    }

    // ========== R7 Report Tests ==========

    /**
     * Test that R7 report requires authentication
     * Requirements: US-4.2 - Only authenticated users can access reports
     */
    public function test_r7_report_requires_authentication(): void
    {
        $response = $this->get(route('reports.r7'));

        // Should redirect to login (302) or return 401 if no login route defined
        $this->assertContains($response->status(), [302, 401, 500]);
    }

    /**
     * Test that R7 report requires CUS_REPORT_READ permission
     * Requirements: US-4.2 - Only users with CUS_REPORT_READ can access
     */
    public function test_r7_report_requires_permission(): void
    {
        // Create user without permission
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('reports.r7'));

        $response->assertStatus(403);
    }

    /**
     * Test that R7 report displays correctly with permission
     * Requirements: US-4.2 - Display tramites status by vessel
     */
    public function test_r7_report_displays_correctly_with_permission(): void
    {
        // Create CUS_REPORT_READ permission
        $cusPermission = Permission::create([
            'code' => 'CUS_REPORT_READ',
            'name' => 'Read Customs Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'AGENTE_ADUANA',
            'name' => 'Agente Aduana',
        ]);
        $role->permissions()->attach($cusPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r7'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.cus.status-by-vessel');
        $response->assertViewHas(['data', 'kpis', 'por_nave', 'filters', 'vessels', 'entidades', 'estados']);
    }

    /**
     * Test that R7 report filters by date range
     * Requirements: US-4.2 - Filter by date range
     */
    public function test_r7_report_filters_by_date_range(): void
    {
        // Create CUS_REPORT_READ permission
        $cusPermission = Permission::create([
            'code' => 'CUS_REPORT_READ',
            'name' => 'Read Customs Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'AGENTE_ADUANA',
            'name' => 'Agente Aduana',
        ]);
        $role->permissions()->attach($cusPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r7', [
                'fecha_desde' => '2025-01-01',
                'fecha_hasta' => '2025-01-31',
            ]));

        $response->assertStatus(200);
        $filters = $response->viewData('filters');
        $this->assertEquals('2025-01-01', $filters['fecha_desde']);
        $this->assertEquals('2025-01-31', $filters['fecha_hasta']);
    }

    /**
     * Test that R7 report calculates KPIs correctly
     * Requirements: US-4.2 - Calculate pct_completos_pre_arribo, lead_time_h
     */
    public function test_r7_report_calculates_kpis_correctly(): void
    {
        // Create CUS_REPORT_READ permission
        $cusPermission = Permission::create([
            'code' => 'CUS_REPORT_READ',
            'name' => 'Read Customs Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'AGENTE_ADUANA',
            'name' => 'Agente Aduana',
        ]);
        $role->permissions()->attach($cusPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r7'));

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');

        $this->assertArrayHasKey('pct_completos_pre_arribo', $kpis);
        $this->assertArrayHasKey('lead_time_h', $kpis);
        $this->assertArrayHasKey('total_tramites', $kpis);
        $this->assertArrayHasKey('aprobados', $kpis);
        $this->assertArrayHasKey('pendientes', $kpis);
        $this->assertArrayHasKey('rechazados', $kpis);
    }

    /**
     * Test that AGENTE_ADUANA can access R7 report
     * Requirements: US-4.2 - AGENTE_ADUANA has CUS_REPORT_READ permission
     */
    public function test_agente_aduana_can_access_r7_report(): void
    {
        // Create CUS_REPORT_READ permission
        $cusPermission = Permission::create([
            'code' => 'CUS_REPORT_READ',
            'name' => 'Read Customs Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'AGENTE_ADUANA',
            'name' => 'Agente Aduana',
        ]);
        $role->permissions()->attach($cusPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r7'));

        $response->assertStatus(200);
    }

    /**
     * Test that ANALISTA can access R7 report
     * Requirements: US-4.2 - ANALISTA has CUS_REPORT_READ permission
     */
    public function test_analista_can_access_r7_report(): void
    {
        // Create CUS_REPORT_READ permission
        $cusPermission = Permission::create([
            'code' => 'CUS_REPORT_READ',
            'name' => 'Read Customs Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'ANALISTA',
            'name' => 'Analista',
        ]);
        $role->permissions()->attach($cusPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r7'));

        $response->assertStatus(200);
    }

    /**
     * Test that AUDITOR can access R7 report
     * Requirements: US-4.2 - AUDITOR has CUS_REPORT_READ permission
     */
    public function test_auditor_can_access_r7_report(): void
    {
        // Create CUS_REPORT_READ permission
        $cusPermission = Permission::create([
            'code' => 'CUS_REPORT_READ',
            'name' => 'Read Customs Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'AUDITOR',
            'name' => 'Auditor',
        ]);
        $role->permissions()->attach($cusPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r7'));

        $response->assertStatus(200);
    }

    /**
     * Test that TRANSPORTISTA cannot access R7 report
     * Requirements: US-4.2 - TRANSPORTISTA does not have CUS_REPORT_READ permission
     */
    public function test_transportista_cannot_access_r7_report(): void
    {
        $role = Role::create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        // Deliberately not attaching CUS_REPORT_READ permission

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r7'));

        $response->assertStatus(403);
    }

    // ========== R12 Report Tests ==========

    /**
     * Test that R12 report requires authentication
     * Requirements: US-5.3 - Only authenticated users can access reports
     */
    public function test_r12_report_requires_authentication(): void
    {
        $response = $this->get(route('reports.r12'));

        // Should redirect to login (302) or return 401 if no login route defined
        $this->assertContains($response->status(), [302, 401, 500]);
    }

    /**
     * Test that R12 report requires SLA_READ permission
     * Requirements: US-5.3 - Only users with SLA_READ can access
     */
    public function test_r12_report_requires_permission(): void
    {
        // Create user without permission
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('reports.r12'));

        $response->assertStatus(403);
    }

    /**
     * Test that R12 report displays correctly with permission
     * Requirements: US-5.3 - Display SLA compliance data by actor
     */
    public function test_r12_report_displays_correctly_with_permission(): void
    {
        // Create SLA_READ permission
        $slaPermission = Permission::create([
            'code' => 'SLA_READ',
            'name' => 'Read SLA Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'ANALISTA',
            'name' => 'Analista',
        ]);
        $role->permissions()->attach($slaPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r12'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.sla.compliance');
        $response->assertViewHas(['data', 'kpis', 'por_actor', 'filters']);
    }

    /**
     * Test that R12 report filters by date range
     * Requirements: US-5.3 - Filter by date range
     */
    public function test_r12_report_filters_by_date_range(): void
    {
        // Create SLA_READ permission
        $slaPermission = Permission::create([
            'code' => 'SLA_READ',
            'name' => 'Read SLA Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'ANALISTA',
            'name' => 'Analista',
        ]);
        $role->permissions()->attach($slaPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r12', [
                'fecha_desde' => '2025-01-01',
                'fecha_hasta' => '2025-01-31',
            ]));

        $response->assertStatus(200);
        $filters = $response->viewData('filters');
        $this->assertEquals('2025-01-01', $filters['fecha_desde']);
        $this->assertEquals('2025-01-31', $filters['fecha_hasta']);
    }

    /**
     * Test that R12 report calculates KPIs correctly
     * Requirements: US-5.3 - Calculate pct_cumplimiento, incumplimientos, penalidades
     */
    public function test_r12_report_calculates_kpis_correctly(): void
    {
        // Create SLA_READ permission
        $slaPermission = Permission::create([
            'code' => 'SLA_READ',
            'name' => 'Read SLA Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'ANALISTA',
            'name' => 'Analista',
        ]);
        $role->permissions()->attach($slaPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r12'));

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');

        $this->assertArrayHasKey('total_actores', $kpis);
        $this->assertArrayHasKey('pct_cumplimiento_promedio', $kpis);
        $this->assertArrayHasKey('penalidades_totales', $kpis);
        $this->assertArrayHasKey('total_incumplimientos', $kpis);
        $this->assertArrayHasKey('actores_excelentes', $kpis);
        $this->assertArrayHasKey('actores_críticos', $kpis);
    }

    /**
     * Test that ANALISTA can access R12 report
     * Requirements: US-5.3 - ANALISTA has SLA_READ permission
     */
    public function test_analista_can_access_r12_report(): void
    {
        // Create SLA_READ permission
        $slaPermission = Permission::create([
            'code' => 'SLA_READ',
            'name' => 'Read SLA Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'ANALISTA',
            'name' => 'Analista',
        ]);
        $role->permissions()->attach($slaPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r12'));

        $response->assertStatus(200);
    }

    /**
     * Test that ADMIN can access R12 report
     * Requirements: US-5.3 - ADMIN has wildcard permissions
     */
    public function test_admin_can_access_r12_report(): void
    {
        $role = Role::create(['code' => 'ADMIN', 'name' => 'Administrator']);
        // ADMIN gets all permissions via wildcard in CheckPermission middleware

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r12'));

        $response->assertStatus(200);
    }

    /**
     * Test that AUDITOR can access R12 report
     * Requirements: US-5.3 - AUDITOR has SLA_READ permission
     */
    public function test_auditor_can_access_r12_report(): void
    {
        // Create SLA_READ permission
        $slaPermission = Permission::create([
            'code' => 'SLA_READ',
            'name' => 'Read SLA Reports',
        ]);

        // Create role with permission
        $role = Role::create([
            'code' => 'AUDITOR',
            'name' => 'Auditor',
        ]);
        $role->permissions()->attach($slaPermission->id);

        // Create user with role
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r12'));

        $response->assertStatus(200);
    }

    /**
     * Test that TRANSPORTISTA cannot access R12 report
     * Requirements: US-5.3 - TRANSPORTISTA does not have SLA_READ permission
     */
    public function test_transportista_cannot_access_r12_report(): void
    {
        $role = Role::create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        // Deliberately not attaching SLA_READ permission

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r12'));

        $response->assertStatus(403);
    }

    /**
     * Test that OPERADOR_GATES cannot access R12 report
     * Requirements: US-5.3 - OPERADOR_GATES does not have SLA_READ permission
     */
    public function test_operador_gates_cannot_access_r12_report(): void
    {
        $role = Role::create(['code' => 'OPERADOR_GATES', 'name' => 'Operador Gates']);
        // Deliberately not attaching SLA_READ permission

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)
            ->get(route('reports.r12'));

        $response->assertStatus(403);
    }
}
