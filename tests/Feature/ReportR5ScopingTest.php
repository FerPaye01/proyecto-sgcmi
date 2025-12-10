<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Truck;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportR5ScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::factory()->create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        Role::factory()->create(['code' => 'ANALISTA', 'name' => 'Analista']);
        
        // Create permissions
        Permission::factory()->create(['code' => 'ROAD_REPORT_READ', 'name' => 'Leer Reportes Terrestres']);
        
        // Assign permissions to roles
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $analistaRole = Role::where('code', 'ANALISTA')->first();
        
        $permission = Permission::where('code', 'ROAD_REPORT_READ')->first();
        $transportistaRole->permissions()->attach($permission);
        $analistaRole->permissions()->attach($permission);
    }

    public function test_r5_report_applies_scoping_for_transportista(): void
    {
        // Create companies
        $company1 = Company::factory()->create(['name' => 'Transportes ABC']);
        $company2 = Company::factory()->create(['name' => 'Transportes XYZ']);

        // Create trucks
        $truck1 = Truck::factory()->create(['company_id' => $company1->id]);
        $truck2 = Truck::factory()->create(['company_id' => $company2->id]);

        // Create appointments for company1
        $appointment1 = Appointment::factory()->create([
            'company_id' => $company1->id,
            'truck_id' => $truck1->id,
            'hora_programada' => now()->subHours(2),
            'hora_llegada' => now()->subHours(2)->addMinutes(10), // 10 min late (A tiempo)
            'estado' => 'ATENDIDA',
        ]);

        $appointment2 = Appointment::factory()->create([
            'company_id' => $company1->id,
            'truck_id' => $truck1->id,
            'hora_programada' => now()->subHours(3),
            'hora_llegada' => null, // No show
            'estado' => 'NO_SHOW',
        ]);

        // Create appointments for company2
        $appointment3 = Appointment::factory()->create([
            'company_id' => $company2->id,
            'truck_id' => $truck2->id,
            'hora_programada' => now()->subHours(1),
            'hora_llegada' => now()->subHours(1)->addMinutes(20), // 20 min late (Tarde)
            'estado' => 'ATENDIDA',
        ]);

        // Create TRANSPORTISTA user for company1
        $transportistaUser = User::factory()->create();
        $transportistaUser->company_id = $company1->id;
        $transportistaUser->save();
        
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $transportistaUser->roles()->attach($transportistaRole);
        $transportistaUser = $transportistaUser->fresh(['roles']);

        // Generate R5 report with scoping
        $reportService = new ReportService();
        $report = $reportService->generateR5([], $transportistaUser);

        // Assert only appointments from company1 are returned
        $this->assertCount(2, $report['data']);
        $appointmentIds = $report['data']->pluck('id')->toArray();
        $this->assertContains($appointment1->id, $appointmentIds);
        $this->assertContains($appointment2->id, $appointmentIds);
        $this->assertNotContains($appointment3->id, $appointmentIds);
    }

    public function test_r5_report_hides_ranking_for_transportista(): void
    {
        // Create companies
        $company1 = Company::factory()->create(['name' => 'Transportes ABC']);
        $company2 = Company::factory()->create(['name' => 'Transportes XYZ']);

        // Create trucks
        $truck1 = Truck::factory()->create(['company_id' => $company1->id]);
        $truck2 = Truck::factory()->create(['company_id' => $company2->id]);

        // Create appointments
        Appointment::factory()->create([
            'company_id' => $company1->id,
            'truck_id' => $truck1->id,
            'hora_programada' => now()->subHours(2),
            'hora_llegada' => now()->subHours(2)->addMinutes(10),
            'estado' => 'ATENDIDA',
        ]);

        Appointment::factory()->create([
            'company_id' => $company2->id,
            'truck_id' => $truck2->id,
            'hora_programada' => now()->subHours(1),
            'hora_llegada' => now()->subHours(1)->addMinutes(5),
            'estado' => 'ATENDIDA',
        ]);

        // Create TRANSPORTISTA user
        $transportistaUser = User::factory()->create();
        $transportistaUser->company_id = $company1->id;
        $transportistaUser->save();
        
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $transportistaUser->roles()->attach($transportistaRole);
        $transportistaUser = $transportistaUser->fresh(['roles']);

        // Generate R5 report
        $reportService = new ReportService();
        $report = $reportService->generateR5([], $transportistaUser);

        // Assert ranking is null for TRANSPORTISTA
        $this->assertNull($report['ranking']);
    }

    public function test_r5_report_shows_ranking_for_analista(): void
    {
        // Create companies
        $company1 = Company::factory()->create(['name' => 'Transportes ABC']);
        $company2 = Company::factory()->create(['name' => 'Transportes XYZ']);

        // Create trucks
        $truck1 = Truck::factory()->create(['company_id' => $company1->id]);
        $truck2 = Truck::factory()->create(['company_id' => $company2->id]);

        // Create appointments for company1 (better performance)
        Appointment::factory()->create([
            'company_id' => $company1->id,
            'truck_id' => $truck1->id,
            'hora_programada' => now()->subHours(2),
            'hora_llegada' => now()->subHours(2)->addMinutes(10), // A tiempo
            'estado' => 'ATENDIDA',
        ]);

        Appointment::factory()->create([
            'company_id' => $company1->id,
            'truck_id' => $truck1->id,
            'hora_programada' => now()->subHours(3),
            'hora_llegada' => now()->subHours(3)->addMinutes(5), // A tiempo
            'estado' => 'ATENDIDA',
        ]);

        // Create appointments for company2 (worse performance)
        Appointment::factory()->create([
            'company_id' => $company2->id,
            'truck_id' => $truck2->id,
            'hora_programada' => now()->subHours(1),
            'hora_llegada' => null, // No show
            'estado' => 'NO_SHOW',
        ]);

        Appointment::factory()->create([
            'company_id' => $company2->id,
            'truck_id' => $truck2->id,
            'hora_programada' => now()->subHours(4),
            'hora_llegada' => now()->subHours(4)->addMinutes(20), // Tarde
            'estado' => 'ATENDIDA',
        ]);

        // Create ANALISTA user
        $analistaUser = User::factory()->create();
        $analistaRole = Role::where('code', 'ANALISTA')->first();
        $analistaUser->roles()->attach($analistaRole);
        $analistaUser = $analistaUser->fresh(['roles']);

        // Generate R5 report
        $reportService = new ReportService();
        $report = $reportService->generateR5([], $analistaUser);

        // Assert ranking is present
        $this->assertNotNull($report['ranking']);
        $this->assertCount(2, $report['ranking']);

        // Assert company1 is ranked first (better compliance)
        $firstRanked = $report['ranking']->first();
        $this->assertEquals($company1->id, $firstRanked['company_id']);
        $this->assertEquals('Transportes ABC', $firstRanked['company_name']);
        $this->assertEquals(100.0, $firstRanked['pct_cumplimiento']); // 2/2 on time
    }

    public function test_r5_report_classifies_appointments_correctly(): void
    {
        // Create company
        $company = Company::factory()->create(['name' => 'Transportes ABC']);
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        // Create appointment A TIEMPO (±15 min)
        $appointmentATiempo = Appointment::factory()->create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'hora_programada' => now()->subHours(3),
            'hora_llegada' => now()->subHours(3)->addMinutes(10), // 10 min late
            'estado' => 'ATENDIDA',
        ]);

        // Create appointment TARDE (>15 min)
        $appointmentTarde = Appointment::factory()->create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'hora_programada' => now()->subHours(2),
            'hora_llegada' => now()->subHours(2)->addMinutes(20), // 20 min late
            'estado' => 'ATENDIDA',
        ]);

        // Create appointment NO_SHOW
        $appointmentNoShow = Appointment::factory()->create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'hora_programada' => now()->subHours(1),
            'hora_llegada' => null,
            'estado' => 'NO_SHOW',
        ]);

        // Create TRANSPORTISTA user
        $transportistaUser = User::factory()->create();
        $transportistaUser->company_id = $company->id;
        $transportistaUser->save();
        
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $transportistaUser->roles()->attach($transportistaRole);
        $transportistaUser = $transportistaUser->fresh(['roles']);

        // Generate R5 report
        $reportService = new ReportService();
        $report = $reportService->generateR5([], $transportistaUser);

        // Find appointments in results
        $results = $report['data'];
        $aTiempo = $results->firstWhere('id', $appointmentATiempo->id);
        $tarde = $results->firstWhere('id', $appointmentTarde->id);
        $noShow = $results->firstWhere('id', $appointmentNoShow->id);

        // Assert classifications
        $this->assertEquals('A_TIEMPO', $aTiempo->clasificacion);
        $this->assertEquals('TARDE', $tarde->clasificacion);
        $this->assertEquals('NO_SHOW', $noShow->clasificacion);

        // Verify KPIs
        $kpis = $report['kpis'];
        $this->assertEqualsWithDelta(33.33, $kpis['pct_no_show'], 0.1); // 1/3
        $this->assertEqualsWithDelta(33.33, $kpis['pct_tarde'], 0.1); // 1/3
        $this->assertEquals(3, $kpis['total_citas']);
    }
}

