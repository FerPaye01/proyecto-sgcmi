<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['username' => 'admin', 'email' => 'admin@sgcmi.pe', 'full_name' => 'Administrador Sistema', 'role' => 'ADMIN'],
            ['username' => 'planificador', 'email' => 'planificador@sgcmi.pe', 'full_name' => 'Juan Planificador', 'role' => 'PLANIFICADOR_PUERTO'],
            ['username' => 'operaciones', 'email' => 'operaciones@sgcmi.pe', 'full_name' => 'María Operaciones', 'role' => 'OPERACIONES_PUERTO'],
            ['username' => 'gates', 'email' => 'gates@sgcmi.pe', 'full_name' => 'Pedro Gates', 'role' => 'OPERADOR_GATES'],
            ['username' => 'transportista', 'email' => 'transportista@sgcmi.pe', 'full_name' => 'Carlos Transportista', 'role' => 'TRANSPORTISTA'],
            ['username' => 'aduana', 'email' => 'aduana@sgcmi.pe', 'full_name' => 'Ana Aduana', 'role' => 'AGENTE_ADUANA'],
            ['username' => 'analista', 'email' => 'analista@sgcmi.pe', 'full_name' => 'Luis Analista', 'role' => 'ANALISTA'],
            ['username' => 'directivo', 'email' => 'directivo@sgcmi.pe', 'full_name' => 'Roberto Directivo', 'role' => 'DIRECTIVO'],
            ['username' => 'auditor', 'email' => 'auditor@sgcmi.pe', 'full_name' => 'Sofia Auditor', 'role' => 'AUDITOR'],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'username' => $userData['username'],
                'email' => $userData['email'],
                'full_name' => $userData['full_name'],
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]);

            $role = Role::where('code', $userData['role'])->first();
            $user->roles()->attach($role);
        }
    }
}
