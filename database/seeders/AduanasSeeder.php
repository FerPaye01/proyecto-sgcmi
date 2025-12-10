<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Entidad;
use App\Models\Tramite;
use Illuminate\Database\Seeder;

class AduanasSeeder extends Seeder
{
    public function run(): void
    {
        $entidades = [
            ['code' => 'SUNAT', 'name' => 'Superintendencia Nacional de Aduanas'],
            ['code' => 'VUCE', 'name' => 'Ventanilla Única de Comercio Exterior'],
            ['code' => 'SENASA', 'name' => 'Servicio Nacional de Sanidad Agraria'],
        ];

        foreach ($entidades as $entidad) {
            Entidad::create($entidad);
        }

        Tramite::create([
            'tramite_ext_id' => 'TRM2024001',
            'vessel_call_id' => 1,
            'regimen' => 'IMPORTACION',
            'subpartida' => '8703.23.00.00',
            'estado' => 'EN_PROCESO',
            'fecha_inicio' => now()->subDays(5),
            'entidad_id' => 1,
        ]);

        Tramite::create([
            'tramite_ext_id' => 'TRM2024002',
            'vessel_call_id' => 1,
            'regimen' => 'EXPORTACION',
            'subpartida' => '0709.60.00.00',
            'estado' => 'COMPLETO',
            'fecha_inicio' => now()->subDays(10),
            'fecha_fin' => now()->subDays(2),
            'entidad_id' => 2,
        ]);
    }
}
