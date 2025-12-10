<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Actor;
use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\Setting;
use App\Models\SlaDefinition;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $kpis = [
            ['code' => 'turnaround_h', 'name' => 'Turnaround Time (horas)', 'description' => 'Tiempo total de permanencia de nave en puerto'],
            ['code' => 'espera_camion_h', 'name' => 'Tiempo Espera Camión (horas)', 'description' => 'Tiempo promedio de espera de camiones'],
            ['code' => 'cumpl_citas_pct', 'name' => 'Cumplimiento Citas (%)', 'description' => 'Porcentaje de citas cumplidas a tiempo'],
            ['code' => 'tramites_ok_pct', 'name' => 'Trámites Completos (%)', 'description' => 'Porcentaje de trámites completados sin incidencias'],
        ];

        foreach ($kpis as $kpi) {
            KpiDefinition::create($kpi);
        }

        KpiValue::create([
            'kpi_id' => 1,
            'periodo' => now()->subDays(7)->toDateString(),
            'valor' => 48.5,
            'meta' => 36.0,
            'fuente' => 'portuario.vessel_call',
        ]);

        $slas = [
            ['code' => 'TURNAROUND_48H', 'name' => 'Turnaround Máximo', 'umbral' => 48.0, 'comparador' => '<='],
            ['code' => 'ESPERA_CAMION_2H', 'name' => 'Espera Máxima Camión', 'umbral' => 2.0, 'comparador' => '<='],
            ['code' => 'TRAMITE_DESPACHO_24H', 'name' => 'Tiempo Máximo Despacho Trámite', 'umbral' => 24.0, 'comparador' => '<='],
        ];

        foreach ($slas as $sla) {
            SlaDefinition::create($sla);
        }

        // Initialize default threshold settings
        $defaultSettings = [
            ['key' => 'alert_berth_utilization', 'value' => '85', 'description' => 'Umbral de utilización de muelles (%) para generar alertas'],
            ['key' => 'alert_truck_waiting_time', 'value' => '4', 'description' => 'Umbral de tiempo de espera de camiones (horas) para generar alertas'],
            ['key' => 'sla_turnaround', 'value' => '48', 'description' => 'SLA de turnaround máximo (horas)'],
            ['key' => 'sla_truck_waiting_time', 'value' => '2', 'description' => 'SLA de tiempo de espera máximo de camiones (horas)'],
            ['key' => 'sla_customs_dispatch', 'value' => '24', 'description' => 'SLA de despacho aduanero máximo (horas)'],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                ]
            );
        }

        // Crear actores para empresas transportistas
        // Estos actores se usan para calcular SLAs por empresa
        $companies = \App\Models\Company::all();
        foreach ($companies as $company) {
            Actor::firstOrCreate(
                [
                    'ref_table' => 'terrestre.company',
                    'ref_id' => $company->id,
                ],
                [
                    'tipo' => 'TRANSPORTISTA',
                    'name' => $company->name,
                ]
            );
        }

        // Crear actores para entidades aduaneras
        // Estos actores se usan para calcular SLAs por entidad
        $entidades = \App\Models\Entidad::all();
        foreach ($entidades as $entidad) {
            Actor::firstOrCreate(
                [
                    'ref_table' => 'aduanas.entidad',
                    'ref_id' => $entidad->id,
                ],
                [
                    'tipo' => 'ENTIDAD_ADUANA',
                    'name' => $entidad->name,
                ]
            );
        }
    }
}
