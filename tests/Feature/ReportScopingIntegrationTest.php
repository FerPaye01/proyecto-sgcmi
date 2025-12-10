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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportScopingIntegrationTest extends TestCase
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

    public function test_transportista_can_only_see_own_company_data_in_r4(): void
    {
        // Create companies
        $company1 = Company::factory()->create(['name' => 'Transportes ABC']);
        $company2 = Company::factory()->create(['name' => 'Transportes XYZ']);

        // Create trucks
        $truck1 = Truck::factory()->create(['company_id' => $company1->id]);
        $truck2 = Truck::factory()->create(['company_id' => $company2->id]);

        // Create appointments
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

        // Create TRANSPORTISTA user for company1
        $transportistaUser = User::factory()->create();
        $transportistaUser->company_id = $company1->id;
        $transportistaUser->save();
        
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $transportistaUser->roles()->attach($transportistaRole);

        // Act as TRANSPORTISTA and access R4 report
        $response = $this->actingAs($transportistaUser)->get(route('reports.r4'));

        // Assert response is successful
        $response->assertStatus(200);

        // Assert view has correct data
        $response->assertViewHas('isTransportista', true);
        $response->assertViewHas('data');
        
        // Get the data from the view
        $data = $response->viewData('data');
        
        // Assert only company1 appointments are present
        $this->assertCount(1, $data);
        $this->assertEquals($appointment1->id, $data->first()->id);
    }

    public function test_analista_can_see_all_companies_data_in_r4(): void
    {
        // Create companies
        $company1 = Company::factory()->create(['name' => 'Transportes ABC']);
        $company2 = Company::factory()->create(['name' => 'Transportes XYZ']);

        // Create trucks
        $truck1 = Truck::factory()->create(['company_id' => $company1->id]);
        $truck2 = Truck::factory()->create(['company_id' => $company2->id]);

        // Create appointments
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

        // Create ANALISTA user (no company_id)
        $analistaUser = User::factory()->create();
        $analistaRole = Role::where('code', 'ANALISTA')->first();
        $analistaUser->roles()->attach($analistaRole);

        // Act as ANALISTA and access R4 report
        $response = $this->actingAs($analistaUser)->get(route('reports.r4'));

        // Assert response is successful
        $response->assertStatus(200);

        // Assert view has correct data
        $response->assertViewHas('isTransportista', false);
        $response->assertViewHas('data');
        
        // Get the data from the view
        $data = $response->viewData('data');
        
        // Assert all appointments are present
        $this->assertCount(2, $data);
    }

    public function test_transportista_cannot_see_ranking_in_r5(): void
    {
        // Create company
        $company = Company::factory()->create(['name' => 'Transportes ABC']);
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        // Create appointment
        Appointment::factory()->create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'hora_programada' => now()->subHours(2),
            'hora_llegada' => now()->subHours(2)->addMinutes(10),
            'estado' => 'ATENDIDA',
        ]);

        // Create TRANSPORTISTA user
        $transportistaUser = User::factory()->create();
        $transportistaUser->company_id = $company->id;
        $transportistaUser->save();
        
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $transportistaUser->roles()->attach($transportistaRole);

        // Act as TRANSPORTISTA and access R5 report
        $response = $this->actingAs($transportistaUser)->get(route('reports.r5'));

        // Assert response is successful
        $response->assertStatus(200);

        // Assert view has correct data
        $response->assertViewHas('isTransportista', true);
        $response->assertViewHas('ranking', null);
    }

    public function test_analista_can_see_ranking_in_r5(): void
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

        // Create ANALISTA user
        $analistaUser = User::factory()->create();
        $analistaRole = Role::where('code', 'ANALISTA')->first();
        $analistaUser->roles()->attach($analistaRole);

        // Act as ANALISTA and access R5 report
        $response = $this->actingAs($analistaUser)->get(route('reports.r5'));

        // Assert response is successful
        $response->assertStatus(200);

        // Assert view has correct data
        $response->assertViewHas('isTransportista', false);
        $response->assertViewHas('ranking');
        
        // Get the ranking from the view
        $ranking = $response->viewData('ranking');
        
        // Assert ranking is not null and has data
        $this->assertNotNull($ranking);
        $this->assertCount(2, $ranking);
    }

    public function test_r4_view_shows_scoping_message_for_transportista(): void
    {
        // Create company
        $company = Company::factory()->create(['name' => 'Transportes ABC']);

        // Create TRANSPORTISTA user
        $transportistaUser = User::factory()->create();
        $transportistaUser->company_id = $company->id;
        $transportistaUser->save();
        
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $transportistaUser->roles()->attach($transportistaRole);

        // Act as TRANSPORTISTA and access R4 report
        $response = $this->actingAs($transportistaUser)->get(route('reports.r4'));

        // Assert response contains scoping message
        $response->assertSee('Mostrando solo datos de su empresa');
    }

    public function test_r5_view_hides_ranking_section_for_transportista(): void
    {
        // Create company
        $company = Company::factory()->create(['name' => 'Transportes ABC']);
        $truck = Truck::factory()->create(['company_id' => $company->id]);

        // Create appointment
        Appointment::factory()->create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'hora_programada' => now()->subHours(2),
            'hora_llegada' => now()->subHours(2)->addMinutes(10),
            'estado' => 'ATENDIDA',
        ]);

        // Create TRANSPORTISTA user
        $transportistaUser = User::factory()->create();
        $transportistaUser->company_id = $company->id;
        $transportistaUser->save();
        
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $transportistaUser->roles()->attach($transportistaRole);

        // Act as TRANSPORTISTA and access R5 report
        $response = $this->actingAs($transportistaUser)->get(route('reports.r5'));

        // Assert response does not contain ranking section
        $response->assertDontSee('Ranking de Empresas por Cumplimiento');
    }
}

