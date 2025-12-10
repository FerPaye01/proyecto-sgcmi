<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\CheckPermission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CheckPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_gets_401(): void
    {
        $middleware = new CheckPermission();
        $request = Request::create('/test', 'GET');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('No autenticado');

        $middleware->handle($request, function () {
            return response('OK');
        }, 'SCHEDULE_READ');
    }

    public function test_admin_bypasses_permission_check(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::create(['code' => 'ADMIN', 'name' => 'Administrador']);
        $user->roles()->attach($adminRole);

        Auth::login($user);

        $middleware = new CheckPermission();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'ANY_PERMISSION');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_user_with_permission_can_access(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['code' => 'PLANIFICADOR_PUERTO', 'name' => 'Planificador Puerto']);
        $permission = Permission::create(['code' => 'SCHEDULE_READ', 'name' => 'Leer Programación']);
        
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        Auth::login($user);

        $middleware = new CheckPermission();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'SCHEDULE_READ');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['code' => 'TRANSPORTISTA', 'name' => 'Transportista']);
        $user->roles()->attach($role);

        Auth::login($user);

        $middleware = new CheckPermission();
        $request = Request::create('/test', 'GET');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('No tiene permiso para esta acción');

        $middleware->handle($request, function () {
            return response('OK');
        }, 'SCHEDULE_WRITE');
    }
}
