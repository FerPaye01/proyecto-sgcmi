<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AccessPermit;
use App\Models\AntepuertoQueue;
use App\Models\DigitalPass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DemoAccessControlController extends Controller
{
    public function index()
    {
        // Generar datos de demostración si no existen
        $this->ensureDemoData();

        // Obtener pases digitales con información relacionada
        $digitalPasses = DigitalPass::with(['truck', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($pass) {
                return [
                    'id' => $pass->id,
                    'pass_code' => $pass->pass_code,
                    'qr_code' => $pass->qr_code,
                    'pass_type' => $pass->pass_type,
                    'holder_name' => $pass->holder_name,
                    'holder_dni' => $pass->holder_dni,
                    'truck_placa' => $pass->truck?->placa,
                    'valid_from' => $pass->valid_from->toISOString(),
                    'valid_until' => $pass->valid_until->toISOString(),
                    'status' => $pass->status,
                ];
            });

        // Obtener permisos de acceso con información relacionada
        $accessPermits = AccessPermit::with(['digitalPass', 'cargoItem', 'authorizer'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($permit) {
                return [
                    'id' => $permit->id,
                    'pass_code' => $permit->digitalPass->pass_code,
                    'permit_type' => $permit->permit_type,
                    'container_number' => $permit->cargoItem?->container_number,
                    'authorized_at' => $permit->authorized_at?->toISOString(),
                    'used_at' => $permit->used_at?->toISOString(),
                    'status' => $permit->status,
                ];
            });

        // Obtener cola de antepuerto con información relacionada
        $antepuertoQueues = AntepuertoQueue::with(['truck', 'appointment'])
            ->orderBy('entry_time', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($queue) {
                $waitingTime = null;
                if ($queue->entry_time) {
                    $endTime = $queue->exit_time ?? now();
                    $waitingTime = (int) $queue->entry_time->diffInMinutes($endTime);
                }

                return [
                    'id' => $queue->id,
                    'truck_placa' => $queue->truck->placa,
                    'zone' => $queue->zone,
                    'entry_time' => $queue->entry_time?->toISOString(),
                    'exit_time' => $queue->exit_time?->toISOString(),
                    'waiting_time' => $waitingTime,
                    'status' => $queue->status,
                ];
            });

        // Calcular tiempo promedio de espera
        $avgWaitTime = AntepuertoQueue::whereNotNull('entry_time')
            ->whereNotNull('exit_time')
            ->get()
            ->map(function ($queue) {
                return $queue->entry_time->diffInMinutes($queue->exit_time);
            })
            ->average() ?? 0;

        return view('demo-access-control', [
            'digitalPasses' => $digitalPasses,
            'accessPermits' => $accessPermits,
            'antepuertoQueues' => $antepuertoQueues,
            'avgWaitTime' => $avgWaitTime,
        ]);
    }

    private function ensureDemoData(): void
    {
        // Verificar si ya existen datos
        if (DigitalPass::count() > 0) {
            return;
        }

        DB::transaction(function () {
            // Crear pases digitales de demostración
            $passes = [
                DigitalPass::factory()->active()->vehicular()->create([
                    'holder_name' => 'Juan Pérez García',
                    'holder_dni' => '12345678',
                ]),
                DigitalPass::factory()->active()->personal()->create([
                    'holder_name' => 'María López Sánchez',
                    'holder_dni' => '87654321',
                ]),
                DigitalPass::factory()->expired()->vehicular()->create([
                    'holder_name' => 'Carlos Rodríguez',
                    'holder_dni' => '11223344',
                ]),
                DigitalPass::factory()->revoked()->vehicular()->create([
                    'holder_name' => 'Ana Martínez',
                    'holder_dni' => '44332211',
                ]),
                DigitalPass::factory()->active()->vehicular()->create([
                    'holder_name' => 'Pedro Gómez',
                    'holder_dni' => '55667788',
                ]),
            ];

            // Crear permisos de acceso
            foreach ($passes as $pass) {
                if ($pass->status === 'ACTIVO') {
                    AccessPermit::factory()->pending()->create([
                        'digital_pass_id' => $pass->id,
                    ]);
                    
                    AccessPermit::factory()->used()->create([
                        'digital_pass_id' => $pass->id,
                    ]);
                }
            }

            // Crear cola de antepuerto
            AntepuertoQueue::factory()->inQueue()->antepuerto()->count(3)->create();
            AntepuertoQueue::factory()->inQueue()->zoe()->count(2)->create();
            AntepuertoQueue::factory()->authorized()->antepuerto()->count(4)->create();
            AntepuertoQueue::factory()->rejected()->antepuerto()->count(1)->create();
        });
    }
}
