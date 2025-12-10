<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portuario;

use App\Http\Controllers\Controller;
use App\Models\OperationsMeeting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperationsMeetingController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    /**
     * Display a listing of operations meetings
     * Requirements: 1.4
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', OperationsMeeting::class);

        $query = OperationsMeeting::with(['creator', 'updater'])
            ->orderBy('meeting_date', 'desc')
            ->orderBy('meeting_time', 'desc');

        // Filter by date range if provided
        if ($request->filled('date_from')) {
            $query->where('meeting_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('meeting_date', '<=', $request->date_to);
        }

        $meetings = $query->paginate(15);

        return view('portuario.operations-meeting.index', compact('meetings'));
    }

    /**
     * Show the form for creating a new operations meeting
     * Requirements: 1.4
     */
    public function create()
    {
        $this->authorize('create', OperationsMeeting::class);

        return view('portuario.operations-meeting.create');
    }

    /**
     * Store a newly created operations meeting in storage
     * Requirements: 1.4
     */
    public function store(Request $request)
    {
        $this->authorize('create', OperationsMeeting::class);

        $validated = $request->validate([
            'meeting_date' => ['required', 'date'],
            'meeting_time' => ['required', 'date_format:H:i'],
            'attendees' => ['required', 'array', 'min:1'],
            'attendees.*.name' => ['required', 'string', 'max:255'],
            'attendees.*.role' => ['required', 'string', 'max:255'],
            'agreements' => ['required', 'string', 'max:5000'],
            'next_24h_schedule' => ['required', 'array', 'min:1'],
            'next_24h_schedule.*.vessel' => ['required', 'string', 'max:255'],
            'next_24h_schedule.*.operation' => ['required', 'in:CARGA,DESCARGA,REESTIBA'],
            'next_24h_schedule.*.start_time' => ['required', 'date_format:H:i'],
            'next_24h_schedule.*.estimated_duration_h' => ['required', 'numeric', 'min:0', 'max:48'],
        ]);

        $meeting = OperationsMeeting::create([
            'meeting_date' => $validated['meeting_date'],
            'meeting_time' => $validated['meeting_time'],
            'attendees' => $validated['attendees'],
            'agreements' => $validated['agreements'],
            'next_24h_schedule' => $validated['next_24h_schedule'],
            'created_by' => Auth::id(),
        ]);

        // Log audit event
        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'portuario',
            objectTable: 'operations_meeting',
            objectId: $meeting->id,
            details: [
                'meeting_date' => $meeting->meeting_date->toDateString(),
                'meeting_time' => $meeting->meeting_time,
                'attendees_count' => count($validated['attendees']),
                'schedule_items_count' => count($validated['next_24h_schedule']),
            ]
        );

        return redirect()
            ->route('operations-meeting.show', $meeting)
            ->with('success', 'Junta de Operaciones registrada exitosamente');
    }

    /**
     * Display the specified operations meeting
     * Requirements: 1.4
     */
    public function show(OperationsMeeting $operationsMeeting)
    {
        $this->authorize('view', $operationsMeeting);

        $operationsMeeting->load(['creator', 'updater']);

        return view('portuario.operations-meeting.show', compact('operationsMeeting'));
    }

    /**
     * Show the form for editing the specified operations meeting
     * Requirements: 1.4
     */
    public function edit(OperationsMeeting $operationsMeeting)
    {
        $this->authorize('update', $operationsMeeting);

        return view('portuario.operations-meeting.edit', compact('operationsMeeting'));
    }

    /**
     * Update the specified operations meeting in storage
     * Requirements: 1.4
     */
    public function update(Request $request, OperationsMeeting $operationsMeeting)
    {
        $this->authorize('update', $operationsMeeting);

        $validated = $request->validate([
            'meeting_date' => ['required', 'date'],
            'meeting_time' => ['required', 'date_format:H:i'],
            'attendees' => ['required', 'array', 'min:1'],
            'attendees.*.name' => ['required', 'string', 'max:255'],
            'attendees.*.role' => ['required', 'string', 'max:255'],
            'agreements' => ['required', 'string', 'max:5000'],
            'next_24h_schedule' => ['required', 'array', 'min:1'],
            'next_24h_schedule.*.vessel' => ['required', 'string', 'max:255'],
            'next_24h_schedule.*.operation' => ['required', 'in:CARGA,DESCARGA,REESTIBA'],
            'next_24h_schedule.*.start_time' => ['required', 'date_format:H:i'],
            'next_24h_schedule.*.estimated_duration_h' => ['required', 'numeric', 'min:0', 'max:48'],
        ]);

        $operationsMeeting->update([
            'meeting_date' => $validated['meeting_date'],
            'meeting_time' => $validated['meeting_time'],
            'attendees' => $validated['attendees'],
            'agreements' => $validated['agreements'],
            'next_24h_schedule' => $validated['next_24h_schedule'],
            'updated_by' => Auth::id(),
        ]);

        // Log audit event
        $this->auditService->log(
            action: 'UPDATE',
            objectSchema: 'portuario',
            objectTable: 'operations_meeting',
            objectId: $operationsMeeting->id,
            details: [
                'meeting_date' => $operationsMeeting->meeting_date->toDateString(),
                'meeting_time' => $operationsMeeting->meeting_time,
                'attendees_count' => count($validated['attendees']),
                'schedule_items_count' => count($validated['next_24h_schedule']),
            ]
        );

        return redirect()
            ->route('operations-meeting.show', $operationsMeeting)
            ->with('success', 'Junta de Operaciones actualizada exitosamente');
    }

    /**
     * Remove the specified operations meeting from storage
     * Requirements: 1.4
     */
    public function destroy(OperationsMeeting $operationsMeeting)
    {
        $this->authorize('delete', $operationsMeeting);

        // Log audit event before deletion
        $this->auditService->log(
            action: 'DELETE',
            objectSchema: 'portuario',
            objectTable: 'operations_meeting',
            objectId: $operationsMeeting->id,
            details: [
                'meeting_date' => $operationsMeeting->meeting_date->toDateString(),
                'meeting_time' => $operationsMeeting->meeting_time,
            ]
        );

        $operationsMeeting->delete();

        return redirect()
            ->route('operations-meeting.index')
            ->with('success', 'Junta de Operaciones eliminada exitosamente');
    }
}

