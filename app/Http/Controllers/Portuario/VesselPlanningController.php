<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portuario;

use App\Http\Controllers\Controller;
use App\Models\VesselCall;
use App\Models\ShipParticulars;
use App\Models\LoadingPlan;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VesselPlanningController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    /**
     * Show form for creating vessel service request
     * Requirements: 1.1
     */
    public function createServiceRequest()
    {
        $this->authorize('create', VesselCall::class);

        $vessels = \App\Models\Vessel::orderBy('name')->get();
        $berths = \App\Models\Berth::where('active', true)->orderBy('name')->get();

        return view('portuario.vessel-planning.service-request', compact('vessels', 'berths'));
    }

    /**
     * Store vessel service request with ship particulars and loading plan
     * Requirements: 1.1
     */
    public function storeServiceRequest(Request $request)
    {
        $this->authorize('create', VesselCall::class);

        $validated = $request->validate([
            'vessel_id' => ['required', 'integer', 'exists:App\Models\Vessel,id'],
            'viaje_id' => ['nullable', 'string', 'max:255'],
            'berth_id' => ['nullable', 'integer', 'exists:App\Models\Berth,id'],
            'eta' => ['required', 'date', 'after:+47 hours'], // Mínimo 48 horas de anticipación
            'etb' => ['nullable', 'date', 'after_or_equal:eta'],
            'estado_llamada' => ['required', 'in:PROGRAMADA,EN_TRANSITO'],
            
            // Ship Particulars
            'loa' => ['required', 'numeric', 'min:0'],
            'beam' => ['required', 'numeric', 'min:0'],
            'draft' => ['required', 'numeric', 'min:0'],
            'grt' => ['nullable', 'numeric', 'min:0'],
            'nrt' => ['nullable', 'numeric', 'min:0'],
            'dwt' => ['nullable', 'numeric', 'min:0'],
            'ballast_report' => ['nullable', 'array'],
            'dangerous_cargo' => ['nullable', 'array'],
            
            // Loading Plans
            'loading_plans' => ['nullable', 'array'],
            'loading_plans.*.operation_type' => ['required', 'in:CARGA,DESCARGA,REESTIBA'],
            'loading_plans.*.sequence_order' => ['required', 'integer', 'min:1'],
            'loading_plans.*.estimated_duration_h' => ['required', 'numeric', 'min:0'],
            'loading_plans.*.equipment_required' => ['nullable', 'array'],
            'loading_plans.*.crew_required' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            // Create vessel call
            $vesselCall = VesselCall::create([
                'vessel_id' => $validated['vessel_id'],
                'viaje_id' => $validated['viaje_id'] ?? null,
                'berth_id' => $validated['berth_id'] ?? null,
                'eta' => $validated['eta'],
                'etb' => $validated['etb'] ?? null,
                'estado_llamada' => $validated['estado_llamada'],
            ]);

            // Create ship particulars
            ShipParticulars::create([
                'vessel_call_id' => $vesselCall->id,
                'loa' => $validated['loa'],
                'beam' => $validated['beam'],
                'draft' => $validated['draft'],
                'grt' => $validated['grt'] ?? null,
                'nrt' => $validated['nrt'] ?? null,
                'dwt' => $validated['dwt'] ?? null,
                'ballast_report' => $validated['ballast_report'] ?? null,
                'dangerous_cargo' => $validated['dangerous_cargo'] ?? null,
            ]);

            // Create loading plans if provided
            if (!empty($validated['loading_plans'])) {
                foreach ($validated['loading_plans'] as $plan) {
                    LoadingPlan::create([
                        'vessel_call_id' => $vesselCall->id,
                        'operation_type' => $plan['operation_type'],
                        'sequence_order' => $plan['sequence_order'],
                        'estimated_duration_h' => $plan['estimated_duration_h'],
                        'equipment_required' => $plan['equipment_required'] ?? null,
                        'crew_required' => $plan['crew_required'] ?? null,
                        'status' => 'PLANIFICADO',
                    ]);
                }
            }

            // Log audit event
            $this->auditService->log(
                action: 'CREATE',
                objectSchema: 'portuario',
                objectTable: 'vessel_call',
                objectId: $vesselCall->id,
                details: [
                    'vessel_id' => $vesselCall->vessel_id,
                    'viaje_id' => $vesselCall->viaje_id,
                    'eta' => $vesselCall->eta?->toIso8601String(),
                    'ship_particulars' => true,
                    'loading_plans_count' => count($validated['loading_plans'] ?? []),
                ]
            );

            DB::commit();

            return redirect()
                ->route('vessel-planning.show', $vesselCall)
                ->with('success', 'Solicitud de servicio de nave registrada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar la solicitud: ' . $e->getMessage()]);
        }
    }

    /**
     * Validate and approve/reject vessel arrival request
     * Requirements: 1.2
     */
    public function validateArrival(Request $request, VesselCall $vesselCall)
    {
        $this->authorize('update', $vesselCall);

        $validated = $request->validate([
            'approval_status' => ['required', 'in:APPROVED,REJECTED'],
            'approval_reason' => ['required', 'string', 'max:1000'],
            'safety_check' => ['required', 'boolean'],
            'stowage_check' => ['required', 'boolean'],
            'cargo_type_check' => ['required', 'boolean'],
            'particulars_check' => ['required', 'boolean'],
        ]);

        // All checks must pass for approval
        if ($validated['approval_status'] === 'APPROVED') {
            if (!$validated['safety_check'] || !$validated['stowage_check'] || 
                !$validated['cargo_type_check'] || !$validated['particulars_check']) {
                return back()->withErrors([
                    'error' => 'No se puede aprobar: todas las verificaciones deben estar completas'
                ]);
            }
        }

        $oldStatus = $vesselCall->estado_llamada;
        
        $vesselCall->update([
            'estado_llamada' => $validated['approval_status'] === 'APPROVED' ? 'EN_TRANSITO' : 'RECHAZADA',
            'motivo_demora' => $validated['approval_status'] === 'REJECTED' ? $validated['approval_reason'] : null,
        ]);

        // Log audit event
        $this->auditService->log(
            action: 'UPDATE',
            objectSchema: 'portuario',
            objectTable: 'vessel_call',
            objectId: $vesselCall->id,
            details: [
                'action' => 'arrival_validation',
                'old_status' => $oldStatus,
                'new_status' => $vesselCall->estado_llamada,
                'approval_status' => $validated['approval_status'],
                'approval_reason' => $validated['approval_reason'],
                'checks' => [
                    'safety' => $validated['safety_check'],
                    'stowage' => $validated['stowage_check'],
                    'cargo_type' => $validated['cargo_type_check'],
                    'particulars' => $validated['particulars_check'],
                ],
            ]
        );

        return redirect()
            ->route('vessel-planning.show', $vesselCall)
            ->with('success', $validated['approval_status'] === 'APPROVED' 
                ? 'Arribo aprobado exitosamente' 
                : 'Arribo rechazado');
    }

    /**
     * Display vessel planning details
     * Requirements: 1.1, 1.2
     */
    public function show(VesselCall $vesselCall)
    {
        $this->authorize('view', $vesselCall);

        $vesselCall->load([
            'vessel',
            'berth',
            'shipParticulars',
            'loadingPlans' => function ($query) {
                $query->orderBy('sequence_order');
            },
            'resourceAllocations' => function ($query) {
                $query->orderBy('allocated_at', 'desc');
            }
        ]);

        return view('portuario.vessel-planning.show', compact('vesselCall'));
    }
}

