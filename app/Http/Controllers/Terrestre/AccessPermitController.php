<?php

declare(strict_types=1);

namespace App\Http\Controllers\Terrestre;

use App\Http\Controllers\Controller;
use App\Models\AccessPermit;
use App\Models\CargoItem;
use App\Models\DigitalPass;
use App\Models\Truck;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessPermitController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    /**
     * Display a listing of access permits.
     * Lists permits by truck or cargo
     * 
     * Requirements: 3.6
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', AccessPermit::class);

        $query = AccessPermit::with(['digitalPass', 'cargoItem', 'authorizer']);

        // Filter by truck
        if ($request->filled('truck_id')) {
            $query->whereHas('digitalPass', function ($q) use ($request) {
                $q->where('truck_id', $request->truck_id);
            });
        }

        // Filter by cargo item
        if ($request->filled('cargo_item_id')) {
            $query->where('cargo_item_id', $request->cargo_item_id);
        }

        // Filter by permit type
        if ($request->filled('permit_type')) {
            $query->where('permit_type', $request->permit_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $permits = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('terrestre.access-permit.index', compact('permits'));
    }

    /**
     * Show the form for creating a new access permit.
     */
    public function create(Request $request)
    {
        $this->authorize('create', AccessPermit::class);

        // Get digital passes for selection
        $digitalPasses = DigitalPass::where('status', 'ACTIVO')
            ->with('truck')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get cargo items for selection
        $cargoItems = CargoItem::whereIn('status', ['ALMACENADO', 'EN_TRANSITO'])
            ->with('manifest')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('terrestre.access-permit.create', compact('digitalPasses', 'cargoItems'));
    }

    /**
     * Store a newly created access permit in storage.
     * Creates access permit
     * 
     * Requirements: 3.6
     */
    public function store(Request $request)
    {
        $this->authorize('create', AccessPermit::class);

        $validated = $request->validate([
            'digital_pass_id' => ['required', 'integer', 'exists:App\Models\DigitalPass,id'],
            'permit_type' => ['required', 'in:SALIDA,INGRESO'],
            'cargo_item_id' => ['nullable', 'integer', 'exists:App\Models\CargoItem,id'],
        ], [
            'digital_pass_id.required' => 'El pase digital es obligatorio',
            'digital_pass_id.exists' => 'El pase digital seleccionado no existe',
            'permit_type.required' => 'El tipo de permiso es obligatorio',
            'permit_type.in' => 'El tipo de permiso debe ser SALIDA o INGRESO',
            'cargo_item_id.exists' => 'El ítem de carga seleccionado no existe',
        ]);

        // Verify digital pass is active
        $digitalPass = DigitalPass::findOrFail($validated['digital_pass_id']);
        if ($digitalPass->status !== 'ACTIVO') {
            return back()->withErrors([
                'digital_pass_id' => 'El pase digital no está activo',
            ])->withInput();
        }

        // Verify digital pass is not expired
        if ($digitalPass->valid_until && $digitalPass->valid_until < now()) {
            return back()->withErrors([
                'digital_pass_id' => 'El pase digital ha expirado',
            ])->withInput();
        }

        $permit = DB::transaction(function () use ($validated, $request) {
            $permit = AccessPermit::create([
                'digital_pass_id' => $validated['digital_pass_id'],
                'permit_type' => $validated['permit_type'],
                'cargo_item_id' => $validated['cargo_item_id'] ?? null,
                'authorized_by' => $request->user()->id,
                'authorized_at' => now(),
                'status' => 'PENDIENTE',
            ]);

            // Log audit event
            $this->auditService->log(
                action: 'CREATE',
                objectSchema: 'terrestre',
                objectTable: 'access_permit',
                objectId: $permit->id,
                details: [
                    'digital_pass_id' => $permit->digital_pass_id,
                    'permit_type' => $permit->permit_type,
                    'cargo_item_id' => $permit->cargo_item_id,
                    'authorized_by' => $permit->authorized_by,
                    'status' => $permit->status,
                ]
            );

            return $permit;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Permiso de acceso creado exitosamente',
                'data' => $permit->load(['digitalPass', 'cargoItem', 'authorizer']),
            ], 201);
        }

        return redirect()
            ->route('access-permit.index')
            ->with('success', 'Permiso de acceso creado exitosamente');
    }

    /**
     * Validate all required permits before access.
     * Validates: Exit Permit, Booking Note, Access Authorization
     * 
     * Requirements: 3.6
     */
    public function validatePermits(Request $request)
    {
        $validated = $request->validate([
            'digital_pass_id' => ['required', 'integer', 'exists:App\Models\DigitalPass,id'],
            'action' => ['required', 'in:ENTRADA,SALIDA'],
            'cargo_item_id' => ['nullable', 'integer', 'exists:App\Models\CargoItem,id'],
        ], [
            'digital_pass_id.required' => 'El pase digital es obligatorio',
            'digital_pass_id.exists' => 'El pase digital no existe',
            'action.required' => 'La acción es obligatoria',
            'action.in' => 'La acción debe ser ENTRADA o SALIDA',
            'cargo_item_id.exists' => 'El ítem de carga no existe',
        ]);

        $digitalPass = DigitalPass::with('truck')->findOrFail($validated['digital_pass_id']);
        $errors = [];
        $warnings = [];

        // 1. Validate digital pass is active
        if ($digitalPass->status !== 'ACTIVO') {
            $errors[] = 'El pase digital no está activo';
        }

        // 2. Validate digital pass is not expired
        if ($digitalPass->valid_until && $digitalPass->valid_until < now()) {
            $errors[] = 'El pase digital ha expirado';
        }

        // 3. Validate required permits based on action
        if ($validated['action'] === 'SALIDA') {
            // For exit, require Exit Permit (SALIDA)
            $exitPermit = AccessPermit::where('digital_pass_id', $digitalPass->id)
                ->where('permit_type', 'SALIDA')
                ->where('status', 'PENDIENTE')
                ->first();

            if (!$exitPermit) {
                $errors[] = 'Falta Permiso de Salida válido';
            }

            // If cargo is specified, validate Booking Note exists
            if ($validated['cargo_item_id']) {
                $cargoItem = CargoItem::with('manifest')->find($validated['cargo_item_id']);
                
                if (!$cargoItem) {
                    $errors[] = 'Ítem de carga no encontrado';
                } else {
                    // Check if cargo has booking note (bl_number)
                    if (empty($cargoItem->bl_number)) {
                        $errors[] = 'Falta Nota de Embarque (B/L) para la carga';
                    }

                    // Check if cargo is in correct status for dispatch
                    if (!in_array($cargoItem->status, ['ALMACENADO', 'EN_TRANSITO'])) {
                        $errors[] = 'La carga no está en estado válido para despacho';
                    }

                    // Verify exit permit is linked to this cargo
                    if ($exitPermit && $exitPermit->cargo_item_id !== $cargoItem->id) {
                        $warnings[] = 'El Permiso de Salida no está vinculado a esta carga';
                    }
                }
            }
        } elseif ($validated['action'] === 'ENTRADA') {
            // For entry, require Access Authorization (INGRESO)
            $entryPermit = AccessPermit::where('digital_pass_id', $digitalPass->id)
                ->where('permit_type', 'INGRESO')
                ->where('status', 'PENDIENTE')
                ->first();

            if (!$entryPermit) {
                $errors[] = 'Falta Autorización de Ingreso válida';
            }
        }

        // 4. Check if truck has pending appointments
        if ($digitalPass->truck) {
            $pendingAppointment = $digitalPass->truck->appointments()
                ->whereNull('hora_salida')
                ->where('hora_cita', '<=', now()->addHours(2))
                ->first();

            if (!$pendingAppointment && $validated['action'] === 'ENTRADA') {
                $warnings[] = 'No hay cita programada para este camión';
            }
        }

        $isValid = empty($errors);

        $response = [
            'valid' => $isValid,
            'digital_pass' => [
                'id' => $digitalPass->id,
                'pass_code' => $digitalPass->pass_code,
                'status' => $digitalPass->status,
                'holder_name' => $digitalPass->holder_name,
                'truck_placa' => $digitalPass->truck ? '***MASKED***' : null, // PII masked
            ],
            'errors' => $errors,
            'warnings' => $warnings,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($request->expectsJson()) {
            return response()->json($response, $isValid ? 200 : 422);
        }

        if ($isValid) {
            return back()->with('success', 'Validación exitosa. Todos los permisos están en orden.');
        } else {
            return back()->withErrors(['validation' => implode(', ', $errors)])->withInput();
        }
    }
}

