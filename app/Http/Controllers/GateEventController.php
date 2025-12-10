<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreGateEventRequest;
use App\Models\GateEvent;
use App\Services\AuditService;
use Illuminate\Http\Request;

class GateEventController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    /**
     * Display a listing of gate events.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', GateEvent::class);

        $query = GateEvent::with(['gate', 'truck', 'appointment'])
            ->orderBy('event_ts', 'desc');

        // Apply filters
        if ($request->filled('gate_id')) {
            $query->where('gate_id', $request->gate_id);
        }

        if ($request->filled('truck_id')) {
            $query->where('truck_id', $request->truck_id);
        }

        if ($request->filled('truck_placa')) {
            $query->whereHas('truck', function ($q) use ($request) {
                $q->where('placa', 'ILIKE', '%' . $request->truck_placa . '%');
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('event_ts', '>=', $request->fecha_desde . ' 00:00:00');
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('event_ts', '<=', $request->fecha_hasta . ' 23:59:59');
        }

        if ($request->filled('cita_id')) {
            $query->where('cita_id', $request->cita_id);
        }

        $gateEvents = $query->paginate(50);

        return view('terrestre.gate-events.index', compact('gateEvents'));
    }

    /**
     * Store a newly created gate event in storage.
     */
    public function store(StoreGateEventRequest $request)
    {
        $this->authorize('create', GateEvent::class);

        $gateEvent = GateEvent::create($request->validated());

        // Log audit event - mask PII (placa)
        $truck = $gateEvent->truck;
        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'terrestre',
            objectTable: 'gate_event',
            objectId: $gateEvent->id,
            details: [
                'gate_id' => $gateEvent->gate_id,
                'truck_id' => $gateEvent->truck_id,
                'truck_placa' => '***MASKED***', // PII masked per steering rules
                'action' => $gateEvent->action,
                'event_ts' => $gateEvent->event_ts?->toIso8601String(),
                'cita_id' => $gateEvent->cita_id,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Evento de gate registrado exitosamente',
                'data' => $gateEvent->load(['gate', 'truck', 'appointment']),
            ], 201);
        }

        return redirect()
            ->route('gate-events.index')
            ->with('success', 'Evento de gate registrado exitosamente');
    }
}
