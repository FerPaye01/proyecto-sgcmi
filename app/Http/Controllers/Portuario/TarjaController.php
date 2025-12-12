<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portuario;

use App\Http\Controllers\Controller;
use App\Models\CargoItem;
use App\Models\TarjaNote;
use App\Models\VesselCall;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TarjaController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    /**
     * Display a listing of tarja notes
     * Requirements: 2.3
     */
    public function index(Request $request): View
    {
        $query = TarjaNote::with(['cargoItem.manifest.vesselCall', 'creator'])
            ->orderBy('tarja_date', 'desc');

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

        // Filter by condition
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        // Filter by date range
        if ($request->filled('fecha_desde')) {
            $query->where('tarja_date', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('tarja_date', '<=', $request->fecha_hasta);
        }

        // Filter by inspector
        if ($request->filled('inspector_name')) {
            $query->where('inspector_name', 'ILIKE', '%' . $request->inspector_name . '%');
        }

        $tarjaNotes = $query->paginate(50);

        // Get vessel calls for filter dropdown
        $vesselCalls = VesselCall::with('vessel')
            ->orderBy('eta', 'desc')
            ->limit(100)
            ->get();

        return view('portuario.tarja.index', compact('tarjaNotes', 'vesselCalls'));
    }

    /**
     * Show the form for creating a new tarja note
     * Requirements: 2.3
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

        return view('portuario.tarja.create', compact('cargoItem', 'cargoItems'));
    }

    /**
     * Store a newly created tarja note
     * Requirements: 2.3
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cargo_item_id' => ['required', 'exists:pgsql.portuario.cargo_item,id'],
            'tarja_number' => ['required', 'string', 'max:50', 'unique:pgsql.portuario.tarja_note,tarja_number'],
            'tarja_date' => ['required', 'date'],
            'inspector_name' => ['required', 'string', 'max:255'],
            'observations' => ['nullable', 'string'],
            'condition' => ['required', 'string', 'max:50', Rule::in(['BUENO', 'DAÑADO', 'FALTANTE'])],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['string', 'max:500'],
        ]);

        try {
            // Add created_by
            $validated['created_by'] = auth()->id();

            $tarjaNote = TarjaNote::create($validated);

            $this->auditService->log(
                'CREATE',
                'portuario',
                'tarja_note',
                $tarjaNote->id,
                $tarjaNote->toArray()
            );

            return redirect()
                ->route('tarja.index')
                ->with('success', 'Nota de tarja registrada exitosamente');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar nota de tarja: ' . $e->getMessage()]);
        }
    }
}
