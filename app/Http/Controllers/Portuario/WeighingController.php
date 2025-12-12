<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portuario;

use App\Http\Controllers\Controller;
use App\Models\CargoItem;
use App\Models\VesselCall;
use App\Models\WeighTicket;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeighingController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    /**
     * Display a listing of weigh tickets
     * Requirements: 2.4
     */
    public function index(Request $request): View
    {
        $query = WeighTicket::with(['cargoItem.manifest.vesselCall'])
            ->orderBy('weigh_date', 'desc');

        // Filter by vessel call
        if ($request->filled('vessel_call_id')) {
            $query->whereHas('cargoItem.manifest', function ($q) use ($request) {
                $q->where('vessel_call_id', $request->vessel_call_id);
            });
        }

        // Filter by cargo item
        if ($request->filled('cargo_item_id')) {
            $query->where('cargo_item_id', $request->cargo_item_id);
        }

        // Filter by date range
        if ($request->filled('fecha_desde')) {
            $query->where('weigh_date', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('weigh_date', '<=', $request->fecha_hasta);
        }

        // Filter by scale
        if ($request->filled('scale_id')) {
            $query->where('scale_id', $request->scale_id);
        }

        // Filter by operator
        if ($request->filled('operator_name')) {
            $query->where('operator_name', 'ILIKE', '%' . $request->operator_name . '%');
        }

        $weighTickets = $query->paginate(50);

        // Get vessel calls for filter dropdown
        $vesselCalls = VesselCall::with('vessel')
            ->orderBy('eta', 'desc')
            ->limit(100)
            ->get();

        return view('portuario.weighing.index', compact('weighTickets', 'vesselCalls'));
    }

    /**
     * Show the form for creating a new weigh ticket
     * Requirements: 2.4
     */
    public function create(Request $request): View
    {
        // Get cargo item if specified
        $cargoItem = null;
        if ($request->filled('cargo_item_id')) {
            $cargoItem = CargoItem::with(['manifest.vesselCall', 'yardLocation'])
                ->findOrFail($request->cargo_item_id);
        }

        // Get available cargo items for selection
        $cargoItems = CargoItem::with(['manifest.vesselCall'])
            ->whereIn('status', ['EN_TRANSITO', 'ALMACENADO'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return view('portuario.weighing.create', compact('cargoItem', 'cargoItems'));
    }

    /**
     * Store a newly created weigh ticket with automatic net weight calculation
     * Requirements: 2.4
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cargo_item_id' => ['required', 'exists:pgsql.portuario.cargo_item,id'],
            'ticket_number' => ['required', 'string', 'max:50', 'unique:pgsql.portuario.weigh_ticket,ticket_number'],
            'weigh_date' => ['required', 'date'],
            'gross_weight_kg' => ['required', 'numeric', 'min:0'],
            'tare_weight_kg' => ['required', 'numeric', 'min:0'],
            'scale_id' => ['required', 'string', 'max:50'],
            'operator_name' => ['required', 'string', 'max:255'],
        ]);

        try {
            // Net weight will be calculated automatically by the model's booted method
            $weighTicket = WeighTicket::create($validated);

            $this->auditService->log(
                'CREATE',
                'portuario',
                'weigh_ticket',
                $weighTicket->id,
                $weighTicket->toArray()
            );

            return redirect()
                ->route('weighing.index')
                ->with('success', 'Ticket de pesaje registrado exitosamente. Peso neto: ' . number_format((float) $weighTicket->net_weight_kg, 2) . ' kg');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar ticket de pesaje: ' . $e->getMessage()]);
        }
    }
}
