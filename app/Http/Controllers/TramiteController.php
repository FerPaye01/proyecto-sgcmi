<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTramiteRequest;
use App\Http\Requests\UpdateTramiteRequest;
use App\Models\Tramite;
use App\Services\AuditService;
use Illuminate\Http\Request;

class TramiteController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    /**
     * Display a listing of tramites.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Tramite::class);

        $query = Tramite::with(['vesselCall', 'entidad'])
            ->orderBy('fecha_inicio', 'desc');

        // Apply filters
        if ($request->filled('vessel_call_id')) {
            $query->where('vessel_call_id', $request->vessel_call_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('regimen')) {
            $query->where('regimen', $request->regimen);
        }

        if ($request->filled('entidad_id')) {
            $query->where('entidad_id', $request->entidad_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_inicio', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_inicio', '<=', $request->fecha_hasta);
        }

        $tramites = $query->paginate(50);

        return view('aduanas.tramites.index', compact('tramites'));
    }

    /**
     * Show the form for creating a new tramite.
     */
    public function create()
    {
        $this->authorize('create', Tramite::class);

        return view('aduanas.tramites.create');
    }

    /**
     * Store a newly created tramite in storage.
     */
    public function store(StoreTramiteRequest $request)
    {
        $this->authorize('create', Tramite::class);

        $tramite = Tramite::create($request->validated());

        // Log audit event - mask PII (tramite_ext_id)
        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'aduanas',
            objectTable: 'tramite',
            objectId: $tramite->id,
            details: [
                'vessel_call_id' => $tramite->vessel_call_id,
                'regimen' => $tramite->regimen,
                'subpartida' => $tramite->subpartida,
                'estado' => $tramite->estado,
                'fecha_inicio' => $tramite->fecha_inicio?->toIso8601String(),
                'entidad_id' => $tramite->entidad_id,
                // tramite_ext_id is PII and should not be logged
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Trámite creado exitosamente',
                'data' => $tramite->load(['vesselCall', 'entidad']),
            ], 201);
        }

        return redirect()
            ->route('tramites.index')
            ->with('success', 'Trámite creado exitosamente');
    }

    /**
     * Display the specified tramite.
     */
    public function show(Tramite $tramite)
    {
        $this->authorize('view', $tramite);

        $tramite->load(['vesselCall', 'entidad', 'events']);

        return view('aduanas.tramites.show', compact('tramite'));
    }

    /**
     * Show the form for editing the specified tramite.
     */
    public function edit(Tramite $tramite)
    {
        $this->authorize('update', $tramite);

        $vesselCalls = \App\Models\VesselCall::orderBy('eta', 'desc')->get();
        $entidades = \App\Models\Entidad::orderBy('name')->get();

        return view('aduanas.tramites.edit', compact('tramite', 'vesselCalls', 'entidades'));
    }

    /**
     * Update the specified tramite in storage.
     */
    public function update(UpdateTramiteRequest $request, Tramite $tramite)
    {
        $this->authorize('update', $tramite);

        $oldData = $tramite->only([
            'vessel_call_id',
            'regimen',
            'subpartida',
            'estado',
            'fecha_inicio',
            'fecha_fin',
            'entidad_id'
        ]);

        $tramite->update($request->validated());

        // Log audit event - mask PII (tramite_ext_id)
        $this->auditService->log(
            action: 'UPDATE',
            objectSchema: 'aduanas',
            objectTable: 'tramite',
            objectId: $tramite->id,
            details: [
                'old' => [
                    'vessel_call_id' => $oldData['vessel_call_id'],
                    'regimen' => $oldData['regimen'],
                    'subpartida' => $oldData['subpartida'],
                    'estado' => $oldData['estado'],
                    'fecha_inicio' => $oldData['fecha_inicio']?->toIso8601String(),
                    'fecha_fin' => $oldData['fecha_fin']?->toIso8601String(),
                    'entidad_id' => $oldData['entidad_id'],
                ],
                'new' => [
                    'vessel_call_id' => $tramite->vessel_call_id,
                    'regimen' => $tramite->regimen,
                    'subpartida' => $tramite->subpartida,
                    'estado' => $tramite->estado,
                    'fecha_inicio' => $tramite->fecha_inicio?->toIso8601String(),
                    'fecha_fin' => $tramite->fecha_fin?->toIso8601String(),
                    'entidad_id' => $tramite->entidad_id,
                ],
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Trámite actualizado exitosamente',
                'data' => $tramite->load(['vesselCall', 'entidad']),
            ]);
        }

        return redirect()
            ->route('tramites.index')
            ->with('success', 'Trámite actualizado exitosamente');
    }

    /**
     * Remove the specified tramite from storage.
     */
    public function destroy(Tramite $tramite)
    {
        $this->authorize('delete', $tramite);

        $tramiteId = $tramite->id;
        $tramiteData = [
            'vessel_call_id' => $tramite->vessel_call_id,
            'regimen' => $tramite->regimen,
            'subpartida' => $tramite->subpartida,
            'estado' => $tramite->estado,
            'fecha_inicio' => $tramite->fecha_inicio?->toIso8601String(),
            'entidad_id' => $tramite->entidad_id,
            // tramite_ext_id is PII and should not be logged
        ];

        $tramite->delete();

        // Log audit event
        $this->auditService->log(
            action: 'DELETE',
            objectSchema: 'aduanas',
            objectTable: 'tramite',
            objectId: $tramiteId,
            details: $tramiteData
        );

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Trámite eliminado exitosamente',
            ]);
        }

        return redirect()
            ->route('tramites.index')
            ->with('success', 'Trámite eliminado exitosamente');
    }

    /**
     * Add an event to a tramite (change of state).
     */
    public function addEvent(Request $request, Tramite $tramite)
    {
        $this->authorize('update', $tramite);

        // Validate the request
        $validated = $request->validate([
            'estado' => 'required|string|in:INICIADO,EN_REVISION,OBSERVADO,APROBADO,RECHAZADO',
            'motivo' => 'nullable|string|max:1000',
        ]);

        $oldEstado = $tramite->estado;

        // Create the event
        $event = $tramite->events()->create([
            'event_ts' => now(),
            'estado' => $validated['estado'],
            'motivo' => $validated['motivo'] ?? null,
        ]);

        // Update the tramite's estado
        $tramite->update([
            'estado' => $validated['estado'],
            // If estado is APROBADO or RECHAZADO, set fecha_fin
            'fecha_fin' => in_array($validated['estado'], ['APROBADO', 'RECHAZADO']) && !$tramite->fecha_fin
                ? now()
                : $tramite->fecha_fin,
        ]);

        // Log audit event - mask PII (tramite_ext_id)
        $this->auditService->log(
            action: 'UPDATE',
            objectSchema: 'aduanas',
            objectTable: 'tramite',
            objectId: $tramite->id,
            details: [
                'action' => 'add_event',
                'old_estado' => $oldEstado,
                'new_estado' => $validated['estado'],
                'event_id' => $event->id,
                'event_ts' => $event->event_ts->toIso8601String(),
                'motivo' => $validated['motivo'] ?? null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Evento registrado exitosamente',
                'data' => [
                    'event' => $event,
                    'tramite' => $tramite->fresh(['vesselCall', 'entidad', 'events']),
                ],
            ], 201);
        }

        return redirect()
            ->route('tramites.show', $tramite)
            ->with('success', 'Evento registrado exitosamente');
    }
}
