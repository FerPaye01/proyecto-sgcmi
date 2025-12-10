<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Gate;
use App\Models\GateEvent;
use App\Models\Truck;
use Illuminate\Database\Seeder;

class TerrestreSeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['ruc' => '20123456789', 'name' => 'Transportes del Sur SAC', 'tipo' => 'TRANSPORTISTA'],
            ['ruc' => '20987654321', 'name' => 'Logística Andina EIRL', 'tipo' => 'TRANSPORTISTA'],
        ];

        foreach ($companies as $company) {
            Company::firstOrCreate(['ruc' => $company['ruc']], $company);
        }

        $trucks = [
            ['placa' => 'ABC123', 'company_id' => 1, 'activo' => true],
            ['placa' => 'DEF456', 'company_id' => 1, 'activo' => true],
            ['placa' => 'GHI789', 'company_id' => 2, 'activo' => true],
        ];

        foreach ($trucks as $truck) {
            Truck::firstOrCreate(['placa' => $truck['placa']], $truck);
        }

        $gates = [
            ['code' => 'G1', 'name' => 'Gate 1 - Entrada Principal'],
            ['code' => 'G2', 'name' => 'Gate 2 - Salida Principal'],
        ];

        foreach ($gates as $gate) {
            Gate::firstOrCreate(['code' => $gate['code']], $gate);
        }

        // Only create appointments if they don't exist
        if (Appointment::count() < 2) {
            Appointment::create([
                'truck_id' => 1,
                'company_id' => 1,
                'vessel_call_id' => 1,
                'hora_programada' => now()->addDays(2)->setTime(10, 0),
                'estado' => 'PROGRAMADA',
            ]);

            Appointment::create([
                'truck_id' => 2,
                'company_id' => 1,
                'vessel_call_id' => 1,
                'hora_programada' => now()->addDays(2)->setTime(11, 0),
                'estado' => 'PROGRAMADA',
            ]);
        }

        // Seed 50 gate events (only if not already seeded)
        if (GateEvent::count() < 50) {
            $this->seedGateEvents();
        }
    }

    private function seedGateEvents(): void
    {
        $truckIds = [1, 2, 3];
        $gateIds = [1, 2];
        $actions = ['ENTRADA', 'SALIDA'];
        
        // Generate 50 gate events over the last 7 days
        for ($i = 0; $i < 50; $i++) {
            $daysAgo = rand(0, 6);
            $hour = rand(6, 20);
            $minute = rand(0, 59);
            
            $eventTs = now()->subDays($daysAgo)->setTime($hour, $minute);
            
            GateEvent::create([
                'gate_id' => $gateIds[array_rand($gateIds)],
                'truck_id' => $truckIds[array_rand($truckIds)],
                'action' => $actions[array_rand($actions)],
                'event_ts' => $eventTs,
                'cita_id' => ($i < 10) ? rand(1, 2) : null, // First 10 events linked to appointments
                'extra' => ($i % 5 === 0) ? ['nota' => 'Evento de prueba ' . ($i + 1)] : null,
            ]);
        }
    }
}
