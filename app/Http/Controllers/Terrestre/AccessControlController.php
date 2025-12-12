<?php

declare(strict_types=1);

namespace App\Http\Controllers\Terrestre;

use App\Http\Controllers\Controller;
use App\Models\AntepuertoQueue;
use App\Models\Truck;
use App\Models\Appointment;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessControlController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Register truck entry to antepuerto
     */
    public function registerAntepuertoEntry(Request $request)
    {
        $validated = $request->validate([
            'truck_id' => 'required|integer',
            'appointment_id' => 'nullable|integer',
            'zone' => 'required|in:ANTEPUERTO,ZOE',
        ]);
        
        // Validate truck exists
        $truck = Truck::find($validated['truck_id']);
        if (!$truck) {
            return back()->withErrors(['truck_id' => 'El vehículo seleccionado no existe.'])->withInput();
        }
        
        // Validate appointment exists if provided
        if (!empty($validated['appointment_id'])) {
            $appointment = Appointment::find($validated['appointment_id']);
            if (!$appointment) {
                return back()->withErrors(['appointment_id' => 'La cita seleccionada no existe.'])->withInput();
            }
        }

        try {
            DB::beginTransaction();

            // Check if truck is already in queue
            $existingQueue = AntepuertoQueue::where('truck_id', $validated['truck_id'])
                ->inQueue()
                ->first();

            if ($existingQueue) {
                return back()->with('error', 'El camión ya está en la cola de espera');
            }

            $queueEntry = AntepuertoQueue::create([
                'truck_id' => $validated['truck_id'],
                'appointment_id' => $validated['appointment_id'] ?? null,
                'entry_time' => now(),
                'zone' => $validated['zone'],
                'status' => 'EN_ESPERA',
            ]);

            // Audit log
            $truck = Truck::find($validated['truck_id']);
            $this->auditService->log(
                'CREATE',
                'terrestre',
                'antepuerto_queue',
                $queueEntry->id,
                [
                    'zone' => $validated['zone'],
                    'truck_placa' => '***', // Masked for PII
                    'appointment_id' => $validated['appointment_id'],
                ]
            );

            DB::commit();

            return back()->with('success', 'Entrada registrada exitosamente en ' . $validated['zone']);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar entrada: ' . $e->getMessage());
        }
    }

    /**
     * Authorize entry from antepuerto to terminal
     */
    public function authorizeTerminalEntry(Request $request)
    {
        $validated = $request->validate([
            'queue_id' => 'required|exists:terrestre.antepuerto_queue,id',
            'action' => 'required|in:AUTORIZAR,RECHAZAR',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $queueEntry = AntepuertoQueue::with('truck')->findOrFail($validated['queue_id']);

            if (!$queueEntry->isInQueue()) {
                return back()->with('error', 'El camión no está en cola de espera');
            }

            $oldData = $queueEntry->toArray();

            if ($validated['action'] === 'AUTORIZAR') {
                $queueEntry->authorize();
                $message = 'Entrada autorizada exitosamente';
            } else {
                $queueEntry->reject();
                $message = 'Entrada rechazada';
            }

            // Audit log
            $this->auditService->log(
                'UPDATE',
                'AntepuertoQueue',
                $queueEntry->id,
                $oldData,
                $queueEntry->fresh()->toArray(),
                $validated['action'] . ' - Placa: ***' . ($validated['reason'] ? ' - Motivo: ' . $validated['reason'] : '')
            );

            DB::commit();

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar autorización: ' . $e->getMessage());
        }
    }

    /**
     * Display current antepuerto queue status
     */
    public function antepuertoStatus()
    {
        $queueEntries = AntepuertoQueue::with(['truck.company', 'appointment'])
            ->antepuerto()
            ->inQueue()
            ->orderBy('entry_time', 'asc')
            ->get();

        $statistics = [
            'total_waiting' => $queueEntries->count(),
            'avg_waiting_time' => $queueEntries->avg(fn($entry) => $entry->getWaitingTimeMinutes()),
            'max_waiting_time' => $queueEntries->max(fn($entry) => $entry->getWaitingTimeMinutes()),
        ];

        return view('terrestre.antepuerto.queue', compact('queueEntries', 'statistics'));
    }

    /**
     * Display ZOE occupancy status
     */
    public function zoeStatus()
    {
        $queueEntries = AntepuertoQueue::with(['truck.company', 'appointment'])
            ->zoe()
            ->inQueue()
            ->orderBy('entry_time', 'asc')
            ->get();

        $statistics = [
            'total_waiting' => $queueEntries->count(),
            'avg_waiting_time' => $queueEntries->avg(fn($entry) => $entry->getWaitingTimeMinutes()),
            'max_waiting_time' => $queueEntries->max(fn($entry) => $entry->getWaitingTimeMinutes()),
        ];

        // Get recent authorized/rejected entries for history
        $recentHistory = AntepuertoQueue::with(['truck.company', 'appointment'])
            ->zoe()
            ->whereIn('status', ['AUTORIZADO', 'RECHAZADO'])
            ->orderBy('exit_time', 'desc')
            ->limit(20)
            ->get();

        return view('terrestre.zoe.status', compact('queueEntries', 'statistics', 'recentHistory'));
    }
}
