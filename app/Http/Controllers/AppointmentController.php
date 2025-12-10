<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Services\AuditService;
use App\Services\ScopingService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    /**
     * Show the form for creating a new appointment.
     */
    public function create()
    {
        $this->authorize('create', Appointment::class);

        return view('terrestre.appointments.create');
    }

    /**
     * Display a listing of appointments.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $query = Appointment::with(['truck', 'company', 'vesselCall'])
            ->orderBy('hora_programada', 'desc');

        // Apply company scoping using ScopingService
        $user = auth()->user();
        $query = ScopingService::applyCompanyScope($query, $user);

        // Apply filters
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('truck_id')) {
            $query->where('truck_id', $request->truck_id);
        }

        if ($request->filled('vessel_call_id')) {
            $query->where('vessel_call_id', $request->vessel_call_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('hora_programada', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('hora_programada', '<=', $request->fecha_hasta);
        }

        $appointments = $query->paginate(50);

        return view('terrestre.appointments.index', compact('appointments'));
    }

    /**
     * Store a newly created appointment in storage.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $this->authorize('create', Appointment::class);

        $appointment = Appointment::create($request->validated());

        // Log audit event - mask PII (placa via truck relationship)
        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'terrestre',
            objectTable: 'appointment',
            objectId: $appointment->id,
            details: [
                'truck_id' => $appointment->truck_id,
                'company_id' => $appointment->company_id,
                'vessel_call_id' => $appointment->vessel_call_id,
                'hora_programada' => $appointment->hora_programada?->toIso8601String(),
                'estado' => $appointment->estado,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cita creada exitosamente',
                'data' => $appointment->load(['truck', 'company', 'vesselCall']),
            ], 201);
        }

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Cita creada exitosamente');
    }

    /**
     * Update the specified appointment in storage.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $oldData = $appointment->only([
            'truck_id',
            'company_id',
            'vessel_call_id',
            'hora_programada',
            'hora_llegada',
            'estado',
            'motivo'
        ]);

        $appointment->update($request->validated());

        // Log audit event
        $this->auditService->log(
            action: 'UPDATE',
            objectSchema: 'terrestre',
            objectTable: 'appointment',
            objectId: $appointment->id,
            details: [
                'old' => [
                    'truck_id' => $oldData['truck_id'],
                    'company_id' => $oldData['company_id'],
                    'vessel_call_id' => $oldData['vessel_call_id'],
                    'hora_programada' => $oldData['hora_programada']?->toIso8601String(),
                    'hora_llegada' => $oldData['hora_llegada']?->toIso8601String(),
                    'estado' => $oldData['estado'],
                ],
                'new' => [
                    'truck_id' => $appointment->truck_id,
                    'company_id' => $appointment->company_id,
                    'vessel_call_id' => $appointment->vessel_call_id,
                    'hora_programada' => $appointment->hora_programada?->toIso8601String(),
                    'hora_llegada' => $appointment->hora_llegada?->toIso8601String(),
                    'estado' => $appointment->estado,
                ],
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cita actualizada exitosamente',
                'data' => $appointment->load(['truck', 'company', 'vesselCall']),
            ]);
        }

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Cita actualizada exitosamente');
    }

    /**
     * Remove the specified appointment from storage.
     */
    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $appointmentId = $appointment->id;
        $appointmentData = [
            'truck_id' => $appointment->truck_id,
            'company_id' => $appointment->company_id,
            'vessel_call_id' => $appointment->vessel_call_id,
            'hora_programada' => $appointment->hora_programada?->toIso8601String(),
            'estado' => $appointment->estado,
        ];

        $appointment->delete();

        // Log audit event
        $this->auditService->log(
            action: 'DELETE',
            objectSchema: 'terrestre',
            objectTable: 'appointment',
            objectId: $appointmentId,
            details: $appointmentData
        );

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cita eliminada exitosamente',
            ]);
        }

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Cita eliminada exitosamente');
    }
}
