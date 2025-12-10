<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\GateEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Truck;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportR4ScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::factory()->create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        Role::factory()->create(['code' => 'OPERADOR_GATES', 'name' => 'Operador de Gates']);
        
        // Create permissions
        Permission::factory()->create(['code' => 'ROAD_REPORT_READ', 'name' => 'Leer Reportes Terrestres']);
        
        // Assign permissions to roles
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $operadorRole = Role::where('code', 'OPERADOR_GATES')->first();
        
        $permission = Permission::where('code', 'ROAD_REPORT_READ')->first();
        $transportistaRole->permissions()->attach($permission);
        $operadorRole->permissions()->attach($permission);
    }

    public function test_r4_report_applies_scoping_for_transportista(): void
    {
        // Create companies
        $company1 = Company::factory()->create(['name' => 'Transportes ABC']);
        $company2 = Company::factory()->create(['name' => 'Transportes XYZ']);

        // Create trucks
        $truck1 = Truck::factory()->create(['company_id' => $company1->id]);
        $truck2 = Truck::factory()->create(['company_id' => $company2->id]);

        // Create appointments with hora_llegada
        $appointment1 = Appointment::factory()->create([
            'company_id' => $company1->id,
            'truck_id' => $truck1->id,
            'hora_llegada' => now()->subHours(2),
            'estado' => 'ATENDIDA',
        ]);

        $appointment2 = Appointment::factory()->create([
            'company_id' => $company2->id,
            'truck_id' => $truck2->id,
            'hora_llegada' => now()->subHours(3),
            'estado' => 'ATENDIDA',
        ]);

        $appointment3 = Appointment::factory()->create([
            'company_id' => $company1->id,
            'truck_id' => $truck1->id,
            'hora_llegada' => now()->subHours(1),
            'estado' => 'ATENDIDA',
        ]);

        // Create gate events for appointments
        GateEvent::factory()->create([
            'truck_id' => $truck1->id,
            'cita_id' => $appointment1->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->subHours(1)->subMinutes(30),
        ]);

        GateEvent::factory()->create([
            'truck_id' => $truck2->id,
            'cita_id' => $appointment2->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->subHours(2)->subMinutes(45),
        ]);

        GateEvent::factory()->create([
            'truck_id' => $truck1->id,
            'cita_id' => $appointment3->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->subMinutes(45),
        ]);

        // Create TRANSPORTISTA user for company1
        $transportistaUser = User::factory()->create();
        $transportistaUser->company_id = $company1->id;
        $transportistaUser->save();
        
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $transportistaUser->roles()->attach($transportistaRole);
        $transportistaUser = $transportistaUser->fresh(['roles']);

        // Generate R4 report with scoping
        $reportService = new ReportService();
        $report = $reportService->generateR4([], $transportistaUser);

        // Assert only appointments from company1 are returned
        $this->assertCount(2, $report['data']);
        $appointmentIds = $report['data']->pluck('id')->toArray();
        $this->assertContains($appointment1->id, $appointmentIds);
        $this->assertContains($appointment3->id, $appointmentIds);
        $this->assertNotContains($appointment2->id, $appointmentIds);
    }

    public function test_r4_report_shows_all_companies_for_operador_gates(): void
    {
        // Create companies
        $company1 = Company::factory()->create(['name' => 'Transportes ABC']);
        $company2 = Company::factory()->create(['name' => 'Transportes XYZ']);

        // Create trucks
        $truck1 = Truck::factory()->create(['company_id' => $company1->id]);
        $truck2 = Truck::factory()->create(['company_id' => $company2->id]);

        // Create appointments with hora_llegada
        $appointment1 = Appointment::factory()->create([
            'company_id' => $company1->id,
            'truck_id' => $truck1->id,
            'hora_llegada' => now()->subHours(2),
            'estado' => 'ATENDIDA',
        ]);

        $appointment2 = Appointment::factory()->create([
            'company_id' => $company2->id,
            'truck_id' => $truck2->id,
            'hora_llegada' => now()->subHours(3),
            'estado' => 'ATENDIDA',
        ]);

        // Create gate events
        GateEvent::factory()->create([
            'truck_id' => $truck1->id,
            'cita_id' => $appointment1->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->subHours(1)->subMinutes(30),
        ]);

        GateEvent::factory()->create([
            'truck_id' => $truck2->id,
            'cita_id' => $appointment2->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->subHours(2)->subMinutes(45),
        ]);

        // Create OPERADOR_GATES user (no company_id)
        $operadorUser = User::factory()->create();
        $operadorRole = Role::where('code', 'OPERADOR_GATES')->first();
        $operadorUser->roles()->attach($operadorRole);
        $operadorUser = $operadorUser->fresh(['roles']);

        // Generate R4 report without scoping
        $reportService = new ReportService();
        $report = $reportService->generateR4([], $operadorUser);

        // Assert all appointments are returned (no scoping)
        $this->assertCount(2, $report['data']);
        $appointmentIds = $report['data']->pluck('id')->toArray();
        $this->assertContains($appointment1->id, $appointmentIds);
        $this->assertContains($appointment2->id, $appointmentIds);
    }

    public function test_r4_report_calculates_kpis_correctly_with_scoping(): void
    {
        // Create company
        $company = Company::factory()->create(['name' => 'Transportes ABC']);
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        // Create appointments with different waiting times
        // Appointment 1: 30 minutes wait
        $appointment1 = Appointment::factory()->create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'hora_llegada' => now()->subHours(2),
            'estado' => 'ATENDIDA',
        ]);
        GateEvent::factory()->create([
            'truck_id' => $truck->id,
            'cita_id' => $appointment1->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->subHours(2)->addMinutes(30),
        ]);

        // Appointment 2: 7 hours wait (exceeds 6h threshold)
        $appointment2 = Appointment::factory()->create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'hora_llegada' => now()->subHours(10),
            'estado' => 'ATENDIDA',
        ]);
        GateEvent::factory()->create([
            'truck_id' => $truck->id,
            'cita_id' => $appointment2->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->subHours(3),
        ]);

        // Appointment 3: 1 hour wait
        $appointment3 = Appointment::factory()->create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'hora_llegada' => now()->subHours(3),
            'estado' => 'ATENDIDA',
        ]);
        GateEvent::factory()->create([
            'truck_id' => $truck->id,
            'cita_id' => $appointment3->id,
            'action' => 'ENTRADA',
            'event_ts' => now()->subHours(2),
        ]);

        // Create TRANSPORTISTA user
        $transportistaUser = User::factory()->create();
        $transportistaUser->company_id = $company->id;
        $transportistaUser->save();
        
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $transportistaUser->roles()->attach($transportistaRole);
        $transportistaUser = $transportistaUser->fresh(['roles']);

        // Generate R4 report
        $reportService = new ReportService();
        $report = $reportService->generateR4([], $transportistaUser);

        // Verify KPIs
        $kpis = $report['kpis'];
        
        // Average wait: (0.5 + 7 + 1) / 3 = 2.83 hours
        $this->assertEqualsWithDelta(2.83, $kpis['espera_promedio_h'], 0.1);
        
        // 1 out of 3 appointments exceeds 6h = 33.33%
        $this->assertEqualsWithDelta(33.33, $kpis['pct_gt_6h'], 0.1);
        
        // Total appointments attended
        $this->assertEquals(3, $kpis['citas_atendidas']);
    }
}

