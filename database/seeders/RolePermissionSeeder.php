<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'USER_ADMIN', 'ROLE_ADMIN', 'AUDIT_READ',
            'SCHEDULE_READ', 'SCHEDULE_WRITE',
            'APPOINTMENT_READ', 'APPOINTMENT_WRITE',
            'GATE_EVENT_READ', 'GATE_EVENT_WRITE',
            'ADUANA_READ', 'ADUANA_WRITE',
            'REPORT_READ', 'REPORT_EXPORT',
            'PORT_REPORT_READ', 'ROAD_REPORT_READ', 'CUS_REPORT_READ',
            'KPI_READ', 'SLA_READ', 'SLA_ADMIN',
        ];

        foreach ($permissions as $perm) {
            Permission::create(['code' => $perm, 'name' => $perm]);
        }

        $roles = [
            'ADMIN' => ['*'],
            'PLANIFICADOR_PUERTO' => ['SCHEDULE_READ', 'SCHEDULE_WRITE', 'PORT_REPORT_READ', 'REPORT_READ', 'REPORT_EXPORT'],
            'OPERACIONES_PUERTO' => ['PORT_REPORT_READ', 'ROAD_REPORT_READ', 'REPORT_READ'],
            'OPERADOR_GATES' => ['APPOINTMENT_READ', 'APPOINTMENT_WRITE', 'GATE_EVENT_READ', 'GATE_EVENT_WRITE', 'ROAD_REPORT_READ'],
            'TRANSPORTISTA' => ['APPOINTMENT_READ', 'ROAD_REPORT_READ'],
            'AGENTE_ADUANA' => ['ADUANA_READ', 'CUS_REPORT_READ'],
            'ANALISTA' => ['REPORT_READ', 'REPORT_EXPORT', 'KPI_READ', 'SLA_READ'],
            'DIRECTIVO' => ['REPORT_READ', 'KPI_READ'],
            'AUDITOR' => ['AUDIT_READ', 'REPORT_READ'],
        ];

        foreach ($roles as $roleCode => $perms) {
            $role = Role::create(['code' => $roleCode, 'name' => $roleCode]);
            
            if ($perms === ['*']) {
                $role->permissions()->attach(Permission::all());
            } else {
                $role->permissions()->attach(Permission::whereIn('code', $perms)->get());
            }
        }
    }
}
