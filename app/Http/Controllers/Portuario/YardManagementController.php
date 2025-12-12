<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portuario;

use App\Http\Controllers\Controller;
use App\Models\CargoItem;
use App\Models\YardLocation;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class YardManagementController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    /**
     * Display yard map with occupancy
     * Requirements: 2.2
     */
    public function index(Request $request): View
    {
        $query = YardLocation::query()
            ->with(['cargoItems' => function ($query) {
                $query->where('status', '!=', 'DESPACHADO');
            }])
            ->where('active', true);

        // Apply filters
        if ($request->filled('zone_code')) {
            $query->where('zone_code', $request->zone_code);
        }

        if ($request->filled('location_type')) {
            $query->where('location_type', $request->location_type);
        }

        if ($request->filled('occupied')) {
            $query->where('occupied', $request->boolean('occupied'));
        }

        $locations = $query->orderBy('zone_code')
            ->orderBy('block_code')
            ->orderBy('row_code')
            ->orderBy('tier')
            ->get();

        // Calculate occupancy statistics
        $totalLocations = YardLocation::where('active', true)->count();
        $occupiedLocations = YardLocation::where('active', true)
            ->where('occupied', true)
            ->count();
        $occupancyRate = $totalLocations > 0 
            ? round(($occupiedLocations / $totalLocations) * 100, 2) 
            : 0;

        // Get unique zones and types for filters
        $zones = YardLocation::where('active', true)
            ->distinct()
            ->pluck('zone_code')
            ->sort()
            ->values();

        $locationTypes = YardLocation::where('active', true)
            ->distinct()
            ->pluck('location_type')
            ->sort()
            ->values();

        return view('portuario.yard.map', compact(
            'locations',
            'totalLocations',
            'occupiedLocations',
            'occupancyRate',
            'zones',
            'locationTypes'
        ));
    }

    /**
     * Display yard locations list with filters
     * Requirements: 2.2
     */
    public function locations(Request $request): View
    {
        $query = YardLocation::query();

        // Apply filters
        if ($request->filled('zone_code')) {
            $query->where('zone_code', $request->zone_code);
        }

        if ($request->filled('location_type')) {
            $query->where('location_type', $request->location_type);
        }

        if ($request->filled('occupied')) {
            $query->where('occupied', $request->boolean('occupied'));
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $locations = $query->orderBy('zone_code')
            ->orderBy('block_code')
            ->orderBy('row_code')
            ->orderBy('tier')
            ->get();

        return view('portuario.yard.locations', compact('locations'));
    }

    /**
     * Show movement registration form
     * Requirements: 2.5
     */
    public function showMovementForm(Request $request): View
    {
        $cargoItems = CargoItem::with(['manifest.vesselCall.vessel'])
            ->whereIn('status', ['EN_TRANSITO', 'ALMACENADO'])
            ->orderBy('created_at', 'desc')
            ->get();

        $locations = YardLocation::where('active', true)
            ->orderBy('zone_code')
            ->orderBy('block_code')
            ->orderBy('row_code')
            ->orderBy('tier')
            ->get();

        $availableLocations = YardLocation::available()
            ->orderBy('zone_code')
            ->orderBy('block_code')
            ->orderBy('row_code')
            ->orderBy('tier')
            ->get();

        return view('portuario.yard.movement-register', compact(
            'cargoItems',
            'locations',
            'availableLocations'
        ));
    }

    /**
     * List available locations by type
     * Requirements: 2.2
     */
    public function availableLocations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_type' => ['nullable', 'string', 'max:50'],
            'zone_code' => ['nullable', 'string', 'max:20'],
            'min_capacity_teu' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = YardLocation::available();

        if (isset($validated['location_type'])) {
            $query->where('location_type', $validated['location_type']);
        }

        if (isset($validated['zone_code'])) {
            $query->where('zone_code', $validated['zone_code']);
        }

        if (isset($validated['min_capacity_teu'])) {
            $query->where('capacity_teu', '>=', $validated['min_capacity_teu']);
        }

        $locations = $query->orderBy('zone_code')
            ->orderBy('block_code')
            ->orderBy('row_code')
            ->orderBy('tier')
            ->get()
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'full_code' => $location->full_location_code,
                    'zone_code' => $location->zone_code,
                    'block_code' => $location->block_code,
                    'row_code' => $location->row_code,
                    'tier' => $location->tier,
                    'location_type' => $location->location_type,
                    'capacity_teu' => $location->capacity_teu,
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $locations->count(),
            'locations' => $locations,
        ]);
    }

    /**
     * Register cargo movement with origin/destination
     * Requirements: 2.5
     */
    public function moveCargoItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cargo_item_id' => ['required', 'exists:portuario.cargo_item,id'],
            'origin_location_id' => ['nullable', 'exists:portuario.yard_location,id'],
            'destination_location_id' => ['required', 'exists:portuario.yard_location,id'],
            'movement_type' => [
                'required',
                'string',
                'max:50',
                Rule::in(['TRACCION', 'TRANSFERENCIA', 'DESPACHO'])
            ],
            'movement_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::beginTransaction();

            $cargoItem = CargoItem::findOrFail($validated['cargo_item_id']);
            $destinationLocation = YardLocation::findOrFail($validated['destination_location_id']);

            // Validate destination is available
            if ($destinationLocation->occupied) {
                return back()->withErrors([
                    'error' => 'La ubicación de destino ya está ocupada'
                ]);
            }

            // Validate origin matches current location if specified
            if (isset($validated['origin_location_id'])) {
                if ($cargoItem->yard_location_id !== $validated['origin_location_id']) {
                    return back()->withErrors([
                        'error' => 'La ubicación de origen no coincide con la ubicación actual del ítem de carga'
                    ]);
                }
            }

            $oldData = $cargoItem->toArray();

            // Free origin location
            if ($cargoItem->yard_location_id) {
                $originLocation = YardLocation::find($cargoItem->yard_location_id);
                if ($originLocation) {
                    $originLocation->update(['occupied' => false]);
                }
            }

            // Update cargo item with new location
            $cargoItem->update([
                'yard_location_id' => $validated['destination_location_id'],
                'status' => $validated['movement_type'] === 'DESPACHO' 
                    ? 'DESPACHADO' 
                    : 'ALMACENADO',
            ]);

            // Mark destination as occupied (unless it's a dispatch)
            if ($validated['movement_type'] !== 'DESPACHO') {
                $destinationLocation->update(['occupied' => true]);
            }

            // Log the movement in audit with full details
            $this->auditService->log(
                'UPDATE',
                'CargoItem',
                $cargoItem->id,
                $oldData,
                array_merge($cargoItem->toArray(), [
                    'movement_type' => $validated['movement_type'],
                    'movement_date' => $validated['movement_date'],
                    'origin_location_id' => $validated['origin_location_id'] ?? $oldData['yard_location_id'] ?? null,
                    'destination_location_id' => $validated['destination_location_id'],
                    'notes' => $validated['notes'] ?? null,
                ]),
                auth()->id()
            );

            DB::commit();

            return redirect()
                ->route('yard.map')
                ->with('success', 'Movimiento de carga registrado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors([
                    'error' => 'Error al registrar movimiento de carga: ' . $e->getMessage()
                ]);
        }
    }

    /**
     * Register seal verification
     * Requirements: 2.6
     */
    public function verifySeals(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cargo_item_id' => ['required', 'exists:portuario.cargo_item,id'],
            'seal_number' => ['required', 'string', 'max:50'],
            'verification_type' => [
                'required',
                'string',
                'max:50',
                Rule::in(['INGRESO', 'SALIDA', 'MANIPULEO'])
            ],
            'verification_date' => ['required', 'date'],
            'verified_by' => ['required', 'string', 'max:255'],
            'seal_condition' => [
                'required',
                'string',
                'max:50',
                Rule::in(['INTACTO', 'DAÑADO', 'FALTANTE', 'REEMPLAZADO'])
            ],
            'observations' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::beginTransaction();

            $cargoItem = CargoItem::findOrFail($validated['cargo_item_id']);
            $oldData = $cargoItem->toArray();

            // Update seal number if it changed or was replaced
            if ($validated['seal_condition'] === 'REEMPLAZADO') {
                $cargoItem->update([
                    'seal_number' => $validated['seal_number'],
                ]);
            }

            // Log seal verification in audit
            $this->auditService->log(
                'UPDATE',
                'CargoItem',
                $cargoItem->id,
                $oldData,
                array_merge($cargoItem->toArray(), [
                    'seal_verification' => [
                        'seal_number' => $validated['seal_number'],
                        'verification_type' => $validated['verification_type'],
                        'verification_date' => $validated['verification_date'],
                        'verified_by' => $validated['verified_by'],
                        'seal_condition' => $validated['seal_condition'],
                        'observations' => $validated['observations'] ?? null,
                    ],
                ]),
                auth()->id()
            );

            DB::commit();

            return back()->with('success', 'Verificación de precinto registrada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors([
                    'error' => 'Error al registrar verificación de precinto: ' . $e->getMessage()
                ]);
        }
    }
}
