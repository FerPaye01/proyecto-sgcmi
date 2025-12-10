<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends RefreshDatabase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;
    private Role $adminRole;
    private Role $userRole;

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

        // Create ADMIN role
        $this->adminRole = Role::create([
            'code' => 'ADMIN',
            'name' => 'Administrator',
        ]);

        // Create regular user role
        $this->userRole = Role::create([
            'code' => 'OPERADOR_GATES',
            'name' => 'Operador de Gates',
        ]);

        // Create admin user
        $this->adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@sgcmi.local',
            'password' => bcrypt('password'),
            'full_name' => 'Administrator',
            'is_active' => true,
        ]);
        $this->adminUser->roles()->attach($this->adminRole);

        // Create regular user
        $this->regularUser = User::create([
            'username' => 'operator',
            'email' => 'operator@sgcmi.local',
            'password' => bcrypt('password'),
            'full_name' => 'Gate Operator',
            'is_active' => true,
        ]);
        $this->regularUser->roles()->attach($this->userRole);
    }

    public function test_admin_can_view_thresholds_settings(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.thresholds.show'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.thresholds');
        $response->assertViewHas('thresholds');
    }

    public function test_non_admin_cannot_view_thresholds_settings(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.settings.thresholds.show'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_view_thresholds_settings(): void
    {
        $response = $this->get(route('admin.settings.thresholds.show'));

        $response->assertRedirect('/login');
    }

    public function test_admin_can_update_thresholds(): void
    {
        $data = [
            'alert_berth_utilization' => 90,
            'alert_truck_waiting_time' => 5,
            'sla_turnaround' => 50,
            'sla_truck_waiting_time' => 2.5,
            'sla_customs_dispatch' => 30,
        ];

        $response = $this->actingAs($this->adminUser)
            ->patch(route('admin.settings.thresholds.update'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify thresholds are persisted in database
        $this->assertEquals(90, \App\Models\Setting::getValue('alert_berth_utilization'));
        $this->assertEquals(5, \App\Models\Setting::getValue('alert_truck_waiting_time'));
        $this->assertEquals(50, \App\Models\Setting::getValue('sla_turnaround'));
        $this->assertEquals(2.5, \App\Models\Setting::getValue('sla_truck_waiting_time'));
        $this->assertEquals(30, \App\Models\Setting::getValue('sla_customs_dispatch'));

        // Verify thresholds are also cached
        $this->assertEquals(90, cache('threshold.alert_berth_utilization'));
        $this->assertEquals(5, cache('threshold.alert_truck_waiting_time'));
        $this->assertEquals(50, cache('threshold.sla_turnaround'));
        $this->assertEquals(2.5, cache('threshold.sla_truck_waiting_time'));
        $this->assertEquals(30, cache('threshold.sla_customs_dispatch'));
    }

    public function test_non_admin_cannot_update_thresholds(): void
    {
        $data = [
            'alert_berth_utilization' => 90,
            'alert_truck_waiting_time' => 5,
            'sla_turnaround' => 50,
            'sla_truck_waiting_time' => 2.5,
            'sla_customs_dispatch' => 30,
        ];

        $response = $this->actingAs($this->regularUser)
            ->patch(route('admin.settings.thresholds.update'), $data);

        $response->assertStatus(403);
    }

    public function test_threshold_validation_fails_with_invalid_data(): void
    {
        $data = [
            'alert_berth_utilization' => 150, // Invalid: > 100
            'alert_truck_waiting_time' => 5,
            'sla_turnaround' => 50,
            'sla_truck_waiting_time' => 2.5,
            'sla_customs_dispatch' => 30,
        ];

        $response = $this->actingAs($this->adminUser)
            ->patch(route('admin.settings.thresholds.update'), $data);

        $response->assertSessionHasErrors('alert_berth_utilization');
    }

    public function test_threshold_update_creates_audit_log(): void
    {
        $data = [
            'alert_berth_utilization' => 90,
            'alert_truck_waiting_time' => 5,
            'sla_turnaround' => 50,
            'sla_truck_waiting_time' => 2.5,
            'sla_customs_dispatch' => 30,
        ];

        $this->actingAs($this->adminUser)
            ->patch(route('admin.settings.thresholds.update'), $data);

        // Verify audit log entry
        $auditLog = \App\Models\AuditLog::where('action', 'UPDATE')
            ->where('object_table', 'settings')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($this->adminUser->id, $auditLog->actor_user);
        $this->assertIsArray($auditLog->details);
    }

    public function test_thresholds_persist_across_requests(): void
    {
        $data = [
            'alert_berth_utilization' => 92,
            'alert_truck_waiting_time' => 3.5,
            'sla_turnaround' => 52,
            'sla_truck_waiting_time' => 1.5,
            'sla_customs_dispatch' => 28,
        ];

        // First request: update thresholds
        $this->actingAs($this->adminUser)
            ->patch(route('admin.settings.thresholds.update'), $data);

        // Second request: verify thresholds are persisted
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.thresholds.show'));

        $response->assertStatus(200);
        $response->assertViewHas('thresholds', function ($thresholds) {
            return $thresholds['alert_berth_utilization'] == 92
                && $thresholds['alert_truck_waiting_time'] == 3.5
                && $thresholds['sla_turnaround'] == 52
                && $thresholds['sla_truck_waiting_time'] == 1.5
                && $thresholds['sla_customs_dispatch'] == 28;
        });
    }
}
