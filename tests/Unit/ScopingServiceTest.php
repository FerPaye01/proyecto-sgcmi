<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\ScopingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::factory()->create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        Role::factory()->create(['code' => 'OPERADOR_GATES', 'name' => 'Operador de Gates']);
    }

    public function test_apply_company_scope_does_not_filter_for_non_transportista_role(): void
    {
        // Create companies
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Create appointments for different companies
        Appointment::factory()->create(['company_id' => $company1->id]);
        Appointment::factory()->create(['company_id' => $company2->id]);

        // Create user with OPERADOR_GATES role
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('code', 'OPERADOR_GATES')->first());

        // Apply scoping
        $query = Appointment::query();
        $scopedQuery = ScopingService::applyCompanyScope($query, $user);
        $results = $scopedQuery->get();

        // Assert all appointments are returned (no filtering)
        $this->assertCount(2, $results);
    }

    public function test_apply_company_scope_returns_empty_for_transportista_without_company(): void
    {
        // Create companies and appointments
        $company1 = Company::factory()->create();
        Appointment::factory()->create(['company_id' => $company1->id]);

        // Create user with TRANSPORTISTA role but no company
        $user = User::factory()->create();
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $user->roles()->attach($transportistaRole);

        // Refresh user to ensure role is loaded
        $user = $user->fresh(['roles']);

        // Apply scoping
        $query = Appointment::query();
        $scopedQuery = ScopingService::applyCompanyScope($query, $user);
        
        // Get the SQL to verify the query is correctly scoped
        $sql = $scopedQuery->toSql();
        
        // Assert the query has a condition that will return empty results
        $this->assertStringContainsString('"id" < ?', $sql);
        
        // Execute the query
        $results = $scopedQuery->get();

        // Assert no appointments are returned (empty result for TRANSPORTISTA without company)
        $this->assertCount(0, $results);
    }
    
    public function test_apply_company_scope_filters_by_company_id_for_transportista(): void
    {
        // Create companies
        $company1 = Company::factory()->create(['name' => 'Transportes ABC']);
        $company2 = Company::factory()->create(['name' => 'Transportes XYZ']);

        // Create appointments for different companies
        $appointment1 = Appointment::factory()->create(['company_id' => $company1->id]);
        $appointment2 = Appointment::factory()->create(['company_id' => $company2->id]);
        $appointment3 = Appointment::factory()->create(['company_id' => $company1->id]);

        // Create user with TRANSPORTISTA role and assign company_id
        $user = User::factory()->create();
        $user->company_id = $company1->id;
        $user->save();
        
        $transportistaRole = Role::where('code', 'TRANSPORTISTA')->first();
        $user->roles()->attach($transportistaRole);

        // Refresh user to ensure role and company_id are loaded
        $user = $user->fresh(['roles']);

        // Apply scoping
        $query = Appointment::query();
        $scopedQuery = ScopingService::applyCompanyScope($query, $user);
        $results = $scopedQuery->get();

        // Assert only appointments from company1 are returned
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('id', $appointment1->id));
        $this->assertTrue($results->contains('id', $appointment3->id));
        $this->assertFalse($results->contains('id', $appointment2->id));
    }
}
