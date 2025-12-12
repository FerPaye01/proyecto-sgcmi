<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CargoManifest;
use App\Models\CargoItem;
use App\Models\YardLocation;
use App\Models\TarjaNote;
use App\Models\WeighTicket;
use App\Models\VesselCall;
use App\Models\User;

echo "Generando datos de prueba para Cargo Management...\n\n";

try {
    $vesselCall = VesselCall::first();
    $user = User::first();
    
    if (!$vesselCall || !$user) {
        echo "❌ Error: Se necesita al menos un VesselCall y un User en la base de datos\n";
        exit(1);
    }
    
    // Generar 5 ubicaciones de patio
    echo "1. Generando ubicaciones de patio...\n";
    $zones = ['A', 'B', 'C', 'D', 'E'];
    $locations = [];
    foreach ($zones as $zone) {
        for ($block = 1; $block <= 3; $block++) {
            $location = new YardLocation();
            $location->zone_code = $zone;
            $location->block_code = str_pad((string)$block, 2, '0', STR_PAD_LEFT);
            $location->row_code = 'R' . rand(1, 5);
            $location->tier = rand(1, 4);
            $location->location_type = 'CONTENEDOR';
            $location->capacity_teu = rand(1, 4);
            $location->occupied = false;
            $location->active = true;
            $location->save();
            $locations[] = $location;
        }
    }
    echo "   ✓ Creadas " . count($locations) . " ubicaciones de patio\n\n";
    
    // Generar 3 manifiestos
    echo "2. Generando manifiestos de carga...\n";
    for ($i = 1; $i <= 3; $i++) {
        $manifest = new CargoManifest();
        $manifest->vessel_call_id = $vesselCall->id;
        $manifest->manifest_number = 'MAN-2024-' . str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $manifest->manifest_date = now()->subDays(rand(1, 30));
        $manifest->total_items = rand(10, 50);
        $manifest->total_weight_kg = rand(50000, 500000);
        $manifest->document_url = 'https://example.com/manifests/' . $manifest->manifest_number . '.pdf';
        $manifest->save();
        
        echo "   ✓ Manifiesto: {$manifest->manifest_number}\n";
        
        // Generar 5 ítems de carga por manifiesto
        for ($j = 1; $j <= 5; $j++) {
            $item = new CargoItem();
            $item->manifest_id = $manifest->id;
            $item->item_number = 'ITEM-' . rand(10000, 99999);
            $item->description = 'Contenedor con mercancía general';
            $item->cargo_type = 'CONTENEDOR';
            $item->container_number = 'MSCU' . rand(1000000, 9999999);
            $item->seal_number = 'SEAL-' . rand(10000, 99999);
            $item->weight_kg = rand(5000, 30000);
            $item->volume_m3 = rand(20, 40);
            $item->bl_number = 'BL-' . rand(10000000, 99999999);
            $item->consignee = 'Empresa ' . chr(65 + rand(0, 25)) . ' S.A.';
            $item->yard_location_id = $locations[array_rand($locations)]->id;
            $item->status = ['EN_TRANSITO', 'ALMACENADO', 'DESPACHADO'][rand(0, 2)];
            $item->save();
            
            // Generar tarja para algunos ítems
            if (rand(0, 1)) {
                $tarja = new TarjaNote();
                $tarja->cargo_item_id = $item->id;
                $tarja->tarja_number = 'TARJA-2024-' . rand(1000, 9999);
                $tarja->tarja_date = now()->subDays(rand(1, 15));
                $tarja->inspector_name = 'Inspector ' . chr(65 + rand(0, 25));
                $tarja->observations = rand(0, 1) ? 'Carga en buen estado' : 'Leve daño en esquina';
                $tarja->condition = ['BUENO', 'DAÑADO'][rand(0, 1)];
                $tarja->created_by = $user->id;
                $tarja->save();
            }
            
            // Generar ticket de pesaje para algunos ítems
            if (rand(0, 1)) {
                $ticket = new WeighTicket();
                $ticket->cargo_item_id = $item->id;
                $ticket->ticket_number = 'WEIGH-2024-' . rand(1000, 9999);
                $ticket->weigh_date = now()->subDays(rand(1, 15));
                $ticket->gross_weight_kg = rand(10000, 35000);
                $ticket->tare_weight_kg = rand(2000, 5000);
                // net_weight_kg se calcula automáticamente
                $ticket->scale_id = 'SCALE-0' . rand(1, 3);
                $ticket->operator_name = 'Operador ' . chr(65 + rand(0, 25));
                $ticket->save();
            }
        }
    }
    
    echo "\n3. Resumen de datos generados:\n";
    echo "   ✓ Ubicaciones de patio: " . YardLocation::count() . "\n";
    echo "   ✓ Manifiestos: " . CargoManifest::count() . "\n";
    echo "   ✓ Ítems de carga: " . CargoItem::count() . "\n";
    echo "   ✓ Notas de tarja: " . TarjaNote::count() . "\n";
    echo "   ✓ Tickets de pesaje: " . WeighTicket::count() . "\n";
    
    echo "\n✅ Datos de prueba generados exitosamente!\n";
    echo "\nPuedes consultar los datos con:\n";
    echo "  php artisan tinker\n";
    echo "  >>> App\\Models\\CargoManifest::with('cargoItems')->get()\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
