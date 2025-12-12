<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreGateEventRequest;
use App\Models\GateEvent;
use App\Models\Truck;
use App\Services\AuditService;
use App\Services\OcrLprService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GateEventController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private OcrLprService $ocrLprService
    ) {
    }

    /**
     * Display a listing of gate events.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', GateEvent::class);

        $query = GateEvent::with(['gate', 'truck', 'appointment'])
            ->orderBy('event_ts', 'desc');

        // Apply filters
        if ($request->filled('gate_id')) {
            $query->where('gate_id', $request->gate_id);
        }

        if ($request->filled('truck_id')) {
            $query->where('truck_id', $request->truck_id);
        }

        if ($request->filled('truck_placa')) {
            $query->whereHas('truck', function ($q) use ($request) {
                $q->where('placa', 'ILIKE', '%' . $request->truck_placa . '%');
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('event_ts', '>=', $request->fecha_desde . ' 00:00:00');
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('event_ts', '<=', $request->fecha_hasta . ' 23:59:59');
        }

        if ($request->filled('cita_id')) {
            $query->where('cita_id', $request->cita_id);
        }

        $gateEvents = $query->paginate(50);

        return view('terrestre.gate-events.index', compact('gateEvents'));
    }

    /**
     * Store a newly created gate event in storage.
     * 
     * Before allowing entry, validates:
     * - Exit Permit (for SALIDA)
     * - Booking Note (for SALIDA with cargo)
     * - Access Authorization (for ENTRADA)
     * 
     * Requirements: 3.6
     */
    public function store(StoreGateEventRequest $request)
    {
        $this->authorize('create', GateEvent::class);

        $validated = $request->validated();
        
        // Validate access permissions before creating gate event
        $validationErrors = $this->validateAccessPermissions(
            (int) $validated['truck_id'],
            $validated['action'],
            isset($validated['extra']['cargo_item_id']) ? (int) $validated['extra']['cargo_item_id'] : null
        );

        if (!empty($validationErrors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación de permisos fallida',
                    'errors' => $validationErrors,
                ], 422);
            }

            return back()
                ->withErrors(['validation' => implode(', ', $validationErrors)])
                ->withInput();
        }

        $gateEvent = GateEvent::create($validated);

        // Mark permits as used if validation passed
        $this->markPermitsAsUsed((int) $validated['truck_id'], $validated['action']);

        // Log audit event - mask PII (placa)
        $truck = $gateEvent->truck;
        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'terrestre',
            objectTable: 'gate_event',
            objectId: $gateEvent->id,
            details: [
                'gate_id' => $gateEvent->gate_id,
                'truck_id' => $gateEvent->truck_id,
                'truck_placa' => '***MASKED***', // PII masked per steering rules
                'action' => $gateEvent->action,
                'event_ts' => $gateEvent->event_ts?->toIso8601String(),
                'cita_id' => $gateEvent->cita_id,
                'permits_validated' => true,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Evento de gate registrado exitosamente',
                'data' => $gateEvent->load(['gate', 'truck', 'appointment']),
            ], 201);
        }

        return redirect()
            ->route('gate-events.index')
            ->with('success', 'Evento de gate registrado exitosamente');
    }

    /**
     * Validate all required access permissions before allowing gate access.
     * 
     * Validates:
     * - Exit Permit for SALIDA
     * - Booking Note (B/L) for SALIDA with cargo
     * - Access Authorization for ENTRADA
     * 
     * Requirements: 3.6
     * 
     * @param int $truckId
     * @param string $action ENTRADA or SALIDA
     * @param int|null $cargoItemId
     * @return array Array of validation error messages (empty if valid)
     */
    private function validateAccessPermissions(int $truckId, string $action, ?int $cargoItemId = null): array
    {
        $errors = [];
        
        // Find active digital pass for this truck
        $digitalPass = \App\Models\DigitalPass::where('truck_id', $truckId)
            ->where('status', 'ACTIVO')
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->first();

        if (!$digitalPass) {
            $errors[] = 'No hay pase digital activo para este camión';
            return $errors;
        }

        // Validate based on action type
        if ($action === 'SALIDA') {
            // Require Exit Permit (SALIDA)
            $exitPermit = \App\Models\AccessPermit::where('digital_pass_id', $digitalPass->id)
                ->where('permit_type', 'SALIDA')
                ->where('status', 'PENDIENTE')
                ->first();

            if (!$exitPermit) {
                $errors[] = 'Falta Permiso de Salida válido';
            }

            // If cargo is specified, validate Booking Note and cargo status
            if ($cargoItemId) {
                $cargoItem = \App\Models\CargoItem::with('manifest')->find($cargoItemId);
                
                if (!$cargoItem) {
                    $errors[] = 'Ítem de carga no encontrado';
                } else {
                    // Check if cargo has booking note (bl_number)
                    if (empty($cargoItem->bl_number)) {
                        $errors[] = 'Falta Nota de Embarque (B/L) para la carga';
                    }

                    // Check if cargo is in correct status for dispatch
                    if (!in_array($cargoItem->status, ['ALMACENADO', 'EN_TRANSITO'])) {
                        $errors[] = 'La carga no está en estado válido para despacho (estado actual: ' . $cargoItem->status . ')';
                    }

                    // Verify exit permit is linked to this cargo
                    if ($exitPermit && $exitPermit->cargo_item_id && $exitPermit->cargo_item_id !== $cargoItem->id) {
                        $errors[] = 'El Permiso de Salida no está vinculado a esta carga';
                    }
                }
            }
        } elseif ($action === 'ENTRADA') {
            // Require Access Authorization (INGRESO)
            $entryPermit = \App\Models\AccessPermit::where('digital_pass_id', $digitalPass->id)
                ->where('permit_type', 'INGRESO')
                ->where('status', 'PENDIENTE')
                ->first();

            if (!$entryPermit) {
                $errors[] = 'Falta Autorización de Ingreso válida';
            }
        }

        return $errors;
    }

    /**
     * Mark access permits as used after successful gate event creation.
     * 
     * @param int $truckId
     * @param string $action
     * @return void
     */
    private function markPermitsAsUsed(int $truckId, string $action): void
    {
        $digitalPass = \App\Models\DigitalPass::where('truck_id', $truckId)
            ->where('status', 'ACTIVO')
            ->first();

        if (!$digitalPass) {
            return;
        }

        $permitType = $action === 'SALIDA' ? 'SALIDA' : 'INGRESO';

        \App\Models\AccessPermit::where('digital_pass_id', $digitalPass->id)
            ->where('permit_type', $permitType)
            ->where('status', 'PENDIENTE')
            ->update([
                'status' => 'USADO',
                'used_at' => now(),
            ]);
    }

    /**
     * Process OCR/LPR data and auto-populate gate event fields
     * 
     * This method simulates automatic plate and container recognition
     * and creates or updates gate event records with the recognized data.
     * 
     * Requirements: 3.2, 3.5
     */
    public function processOcrLprData(Request $request)
    {
        $this->authorize('create', GateEvent::class);

        // Validate input
        $validated = $request->validate([
            'gate_id' => ['required', 'integer', 'exists:App\Models\Gate,id'],
            'action' => ['required', 'in:ENTRADA,SALIDA'],
            'plate_image' => ['nullable', 'string'], // In production, this would be 'image|mimes:jpeg,png,jpg'
            'container_image' => ['nullable', 'string'], // In production, this would be 'image|mimes:jpeg,png,jpg'
            'confidence_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'gate_id.required' => 'El gate es obligatorio',
            'gate_id.exists' => 'El gate seleccionado no existe',
            'action.required' => 'La acción es obligatoria',
            'action.in' => 'La acción debe ser ENTRADA o SALIDA',
            'confidence_threshold.numeric' => 'El umbral de confianza debe ser un número',
            'confidence_threshold.min' => 'El umbral de confianza debe ser al menos 0',
            'confidence_threshold.max' => 'El umbral de confianza no puede exceder 100',
        ]);

        $confidenceThreshold = $validated['confidence_threshold'] ?? 85.0;

        // Process OCR/LPR images
        $plateData = $this->ocrLprService->processPlateImage($validated['plate_image'] ?? null);
        $containerData = $this->ocrLprService->processContainerImage($validated['container_image'] ?? null);

        // Validate confidence scores
        $plateConfidenceOk = $this->ocrLprService->isConfidenceAcceptable(
            $plateData['confidence'],
            $confidenceThreshold
        );
        $containerConfidenceOk = $this->ocrLprService->isConfidenceAcceptable(
            $containerData['confidence'],
            $confidenceThreshold
        );

        if (!$plateConfidenceOk) {
            return response()->json([
                'success' => false,
                'message' => 'Confianza de reconocimiento de placa insuficiente',
                'data' => [
                    'plate_confidence' => $plateData['confidence'],
                    'threshold' => $confidenceThreshold,
                    'plate_data' => $plateData,
                ],
            ], 422);
        }

        // Find or create truck based on recognized plate
        $truck = Truck::where('placa', $plateData['plate'])->first();

        if (!$truck) {
            // In production, this might trigger a manual verification workflow
            return response()->json([
                'success' => false,
                'message' => 'Placa no registrada en el sistema',
                'data' => [
                    'recognized_plate' => $plateData['plate'],
                    'confidence' => $plateData['confidence'],
                    'suggestion' => 'Registre el camión antes de continuar',
                ],
            ], 404);
        }

        // Validate against expected data if appointment exists
        $appointment = null;
        if ($truck->appointments()->whereNull('hora_salida')->exists()) {
            $appointment = $truck->appointments()
                ->whereNull('hora_salida')
                ->orderBy('hora_cita', 'desc')
                ->first();
        }

        // Validate access permissions before creating gate event
        $validationErrors = $this->validateAccessPermissions(
            $truck->id,
            $validated['action'],
            null // OCR/LPR doesn't specify cargo_item_id in this flow
        );

        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validación de permisos fallida',
                'errors' => $validationErrors,
                'ocr_lpr_results' => [
                    'plate' => [
                        'value' => $plateData['plate'],
                        'confidence' => $plateData['confidence'],
                    ],
                ],
            ], 422);
        }

        // Create gate event with OCR/LPR data
        $gateEvent = DB::transaction(function () use ($validated, $truck, $appointment, $plateData, $containerData, $containerConfidenceOk) {
            $extra = [
                'ocr_lpr_data' => [
                    'plate' => $plateData,
                    'container' => $containerData,
                ],
                'auto_populated' => true,
                'container_confidence_ok' => $containerConfidenceOk,
            ];

            // Add container number to extra if confidence is acceptable
            if ($containerConfidenceOk && $containerData['iso_valid']) {
                $extra['container_number'] = $containerData['container_number'];
            }

            return GateEvent::create([
                'gate_id' => $validated['gate_id'],
                'truck_id' => $truck->id,
                'action' => $validated['action'],
                'event_ts' => now(),
                'cita_id' => $appointment?->id,
                'extra' => $extra,
            ]);
        });

        // Mark permits as used
        $this->markPermitsAsUsed($truck->id, $validated['action']);

        // Log audit event
        $this->auditService->log(
            action: 'CREATE',
            objectSchema: 'terrestre',
            objectTable: 'gate_event',
            objectId: $gateEvent->id,
            details: [
                'gate_id' => $gateEvent->gate_id,
                'truck_id' => $gateEvent->truck_id,
                'truck_placa' => '***MASKED***', // PII masked per steering rules
                'action' => $gateEvent->action,
                'event_ts' => $gateEvent->event_ts?->toIso8601String(),
                'cita_id' => $gateEvent->cita_id,
                'ocr_lpr_processed' => true,
                'plate_confidence' => $plateData['confidence'],
                'container_confidence' => $containerData['confidence'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Evento de gate registrado exitosamente mediante OCR/LPR',
            'data' => [
                'gate_event' => $gateEvent->load(['gate', 'truck', 'appointment']),
                'ocr_lpr_results' => [
                    'plate' => [
                        'value' => $plateData['plate'],
                        'confidence' => $plateData['confidence'],
                        'accepted' => $plateConfidenceOk,
                    ],
                    'container' => [
                        'value' => $containerData['container_number'],
                        'confidence' => $containerData['confidence'],
                        'accepted' => $containerConfidenceOk,
                        'iso_valid' => $containerData['iso_valid'],
                    ],
                ],
            ],
        ], 201);
    }
}
