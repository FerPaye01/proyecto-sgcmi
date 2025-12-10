<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreVesselCallRequest;
use App\Http\Requests\UpdateVesselCallRequest;
use App\Models\VesselCall;
use App\Services\AuditService;
use Illuminate\Http\Request;

class VesselCallController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', VesselCall::class);

        $query = VesselCall::with(['vessel', 'berth'])
            ->orderBy('eta', 'desc');

        if ($request->filled('fecha_desde')) {
            $query->where('eta', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('eta', '<=', $request->fecha_hasta);
        }

        if ($request->filled('berth_id')) {
            $query->where('berth_id', $request->berth_id);
        }

        if ($request->filled('vessel_id')) {
            $query->where('vessel_id', $request->vessel_id);
        }

        $vesselCalls = $query->paginate(20);

        // Obtener listas para filtros
        $berths = \App\Models\Berth::where('active', true)
            ->orderBy('name')
            ->get();

        $vessels = \App\Models\Vessel::orderBy('name')
            ->get();

        return view('portuario.vessel-calls.index', compact('vesselCalls', 'berths', 'vessels'));
    }

    public function create()
    {
        $this->authorize('create', VesselCall::class);
        return view('portuario.vessel-calls.create');
    }

    public function store(StoreVesselCallRequest $request)
    {
        $this->authorize('create', VesselCall::class);
        
        $vesselCall = VesselCall::create($request->validated());
        
        // Log audit event
        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'portuario',
            objectTable: 'vessel_call',
            objectId: $vesselCall->id,
            details: [
                'vessel_id' => $vesselCall->vessel_id,
                'viaje_id' => $vesselCall->viaje_id,
                'berth_id' => $vesselCall->berth_id,
                'eta' => $vesselCall->eta?->toIso8601String(),
            ]
        );

        return redirect()
            ->route('vessel-calls.index')
            ->with('success', 'Llamada de nave creada exitosamente');
    }

    public function show(VesselCall $vesselCall)
    {
        $this->authorize('view', $vesselCall);
        
        $vesselCall->load(['vessel', 'berth']);
        
        return view('portuario.vessel-calls.show', compact('vesselCall'));
    }

    public function edit(VesselCall $vesselCall)
    {
        $this->authorize('update', $vesselCall);
        return view('portuario.vessel-calls.edit', compact('vesselCall'));
    }

    public function update(UpdateVesselCallRequest $request, VesselCall $vesselCall)
    {
        $this->authorize('update', $vesselCall);
        
        $oldData = $vesselCall->only(['vessel_id', 'viaje_id', 'berth_id', 'eta', 'etb', 'ata', 'atb', 'atd', 'estado_llamada']);
        
        $vesselCall->update($request->validated());
        
        // Log audit event
        $this->auditService->log(
            action: 'UPDATE',
            objectSchema: 'portuario',
            objectTable: 'vessel_call',
            objectId: $vesselCall->id,
            details: [
                'old' => $oldData,
                'new' => $vesselCall->only(['vessel_id', 'viaje_id', 'berth_id', 'eta', 'etb', 'ata', 'atb', 'atd', 'estado_llamada']),
            ]
        );

        return redirect()
            ->route('vessel-calls.index')
            ->with('success', 'Llamada de nave actualizada exitosamente');
    }

    public function destroy(VesselCall $vesselCall)
    {
        $this->authorize('delete', $vesselCall);
        
        $vesselCallId = $vesselCall->id;
        $vesselCallData = $vesselCall->only(['vessel_id', 'viaje_id', 'berth_id']);
        
        $vesselCall->delete();
        
        // Log audit event
        $this->auditService->log(
            action: 'DELETE',
            objectSchema: 'portuario',
            objectTable: 'vessel_call',
            objectId: $vesselCallId,
            details: $vesselCallData
        );

        return redirect()
            ->route('vessel-calls.index')
            ->with('success', 'Llamada de nave eliminada exitosamente');
    }
}
