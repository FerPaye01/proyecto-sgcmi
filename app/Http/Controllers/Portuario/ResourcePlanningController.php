<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portuario;

use App\Http\Controllers\Controller;
use App\Models\VesselCall;
use App\Models\ResourceAllocation;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResourcePlanningController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    /**
     * List resource allocations by vessel
     * Requirements: 1.3
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', VesselCall::class);

        $query = ResourceAllocation::with(['vesselCall.vessel', 'vesselCall.berth', 'creator'])
            ->orderBy('allocated_at', 'desc');

        // Filter by vessel call if provided
        if ($request->filled('vessel_call_id')) {
            $query->where('vessel_call_id', $request->vessel_call_id);
        }

        // Filter by resource type if provided
        if ($request->filled('resource_type')) {
            $query->where('resource_type', $request->resource_type);
        }

        // Filter by shift if provided
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }

        $allocations = $query->paginate(20);

        // Get vessel calls for filter dropdown
        $vesselCalls = VesselCall::with('vessel')
            ->whereIn('estado_llamada', ['PROGRAMADA', 'EN_TRANSITO', 'ATRACADA', 'OPERANDO'])
            ->orderBy('eta', 'desc')
            ->get();

        return view('portuario.vessel-planning.resource-allocation', compact('allocations', 'vesselCalls'));
    }

    /**
     * Allocate resources to a vessel
     * Requirements: 1.3
     */
    public function allocateResources(Request $request)
    {
        $this->authorize('update', VesselCall::class);

        $validated = $request->validate([
            'vessel_call_id' => ['required', 'integer', 'exists:App\Models\VesselCall,id'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.resource_type' => ['required', 'in:EQUIPO,CUADRILLA,GAVIERO'],
            'allocations.*.resource_name' => ['required', 'string', 'max:255'],
            'allocations.*.quantity' => ['required', 'integer', 'min:1'],
            'allocations.*.shift' => ['required', 'in:MAÑANA,TARDE,NOCHE'],
            'allocations.*.allocated_at' => ['required', 'date'],
        ]);

        $vesselCall = VesselCall::findOrFail($validated['vessel_call_id']);
        $createdAllocations = [];

        foreach ($validated['allocations'] as $allocation) {
            $resourceAllocation = ResourceAllocation::create([
                'vessel_call_id' => $vesselCall->id,
                'resource_type' => $allocation['resource_type'],
                'resource_name' => $allocation['resource_name'],
                'quantity' => $allocation['quantity'],
                'shift' => $allocation['shift'],
                'allocated_at' => $allocation['allocated_at'],
                'created_by' => Auth::id(),
            ]);

            $createdAllocations[] = $resourceAllocation->id;

            // Log audit event for each allocation
            $this->auditService->log(
                action: 'CREATE',
                objectSchema: 'portuario',
                objectTable: 'resource_allocation',
                objectId: $resourceAllocation->id,
                details: [
                    'vessel_call_id' => $vesselCall->id,
                    'resource_type' => $allocation['resource_type'],
                    'resource_name' => $allocation['resource_name'],
                    'quantity' => $allocation['quantity'],
                    'shift' => $allocation['shift'],
                ]
            );
        }

        return redirect()
            ->route('vessel-planning.show', $vesselCall)
            ->with('success', 'Recursos asignados exitosamente (' . count($createdAllocations) . ' asignaciones)');
    }

    /**
     * Update resource allocation
     * Requirements: 1.3
     */
    public function updateAllocation(Request $request, ResourceAllocation $allocation)
    {
        $this->authorize('update', VesselCall::class);

        $validated = $request->validate([
            'resource_type' => ['required', 'in:EQUIPO,CUADRILLA,GAVIERO'],
            'resource_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'shift' => ['required', 'in:MAÑANA,TARDE,NOCHE'],
            'allocated_at' => ['required', 'date'],
        ]);

        $oldData = $allocation->only(['resource_type', 'resource_name', 'quantity', 'shift', 'allocated_at']);

        $allocation->update($validated);

        // Log audit event
        $this->auditService->log(
            action: 'UPDATE',
            objectSchema: 'portuario',
            objectTable: 'resource_allocation',
            objectId: $allocation->id,
            details: [
                'old' => $oldData,
                'new' => $allocation->only(['resource_type', 'resource_name', 'quantity', 'shift', 'allocated_at']),
            ]
        );

        return redirect()
            ->route('vessel-planning.show', $allocation->vessel_call_id)
            ->with('success', 'Asignación de recursos actualizada exitosamente');
    }
}

