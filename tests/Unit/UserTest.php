<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_roles(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'TEST_ROLE']);

        $user->roles()->attach($role);

        $this->assertTrue($user->roles->contains($role));
    }

    public function test_user_has_permission_through_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'TEST_ROLE']);
        $permission = Permission::factory()->create(['code' => 'TEST_PERMISSION']);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('TEST_PERMISSION'));
    }

    public function test_user_without_permission_returns_false(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasPermission('NONEXISTENT_PERMISSION'));
    }

    public function test_inactive_user_is_marked_correctly(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->assertFalse($user->is_active);
    }

    public function test_user_has_role(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['code' => 'ADMIN', 'name' => 'Administrador']);

        $user->roles()->attach($role);

        $this->assertTrue($user->hasRole('ADMIN'));
    }

    public function test_user_without_role_returns_false(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasRole('ADMIN'));
    }
}
