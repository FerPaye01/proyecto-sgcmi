<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Berth;
use App\Models\Vessel;
use App\Models\VesselCall;
use Illuminate\Database\Seeder;

class PortuarioSeeder extends Seeder
{
    public function run(): void
    {
        $berths = [
            ['code' => 'M1', 'name' => 'Muelle 1', 'capacity_teorica' => 50000],
            ['code' => 'M2', 'name' => 'Muelle 2', 'capacity_teorica' => 60000],
            ['code' => 'M3', 'name' => 'Muelle 3', 'capacity_teorica' => 45000],
        ];

        foreach ($berths as $berth) {
            Berth::create($berth);
        }

        $vessels = [
            ['imo' => 'IMO9876543', 'name' => 'MSC MARINA', 'flag_country' => 'Panama', 'type' => 'Container'],
            ['imo' => 'IMO9876544', 'name' => 'MAERSK LIMA', 'flag_country' => 'Denmark', 'type' => 'Container'],
            ['imo' => 'IMO9876545', 'name' => 'CMA CGM ANDES', 'flag_country' => 'France', 'type' => 'Container'],
        ];

        foreach ($vessels as $vessel) {
            Vessel::create($vessel);
        }

        VesselCall::create([
            'vessel_id' => 1,
            'viaje_id' => 'V2024001',
            'berth_id' => 1,
            'eta' => now()->addDays(2),
            'etb' => now()->addDays(2)->addHours(3),
            'estado_llamada' => 'PROGRAMADA',
        ]);

        VesselCall::create([
            'vessel_id' => 2,
            'viaje_id' => 'V2024002',
            'berth_id' => 2,
            'eta' => now()->addDays(5),
            'etb' => now()->addDays(5)->addHours(2),
            'estado_llamada' => 'PROGRAMADA',
        ]);
    }
}
