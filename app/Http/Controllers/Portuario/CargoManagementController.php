<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portuario;

use App\Http\Controllers\Controller;
use App\Models\CargoItem;
use App\Models\CargoManifest;
use App\Models\VesselCall;
use App\Models\YardLocation;
use App\Services\AuditService;
use App\Services\ReportGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CargoManagementController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly ReportGenerationService $reportGenerationService
    ) {
    }

    /**
     * Display a listing of cargo manifests
     * Requirements: 2.1
     */
    public function indexManifests(): View
    {
        $manifests = CargoManifest::with(['vesselCall.vessel'])
            ->orderBy('manifest_date', 'desc')
            ->paginate(20);

        return view('portuario.cargo.manifest-index', compact('manifests'));
    }

    /**
     * Show the form for creating a new cargo manifest
     * Requirements: 2.1
     */
    public function createManifest(): View
    {
        return view('portuario.cargo.manifest-create');
    }

    /**
     * Store a new cargo manifest with documents
     * Requirements: 2.1
     */
    public function storeManifest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vessel_call_id' => ['required', 'exists:portuario.vessel_call,id'],
            'manifest_number' => ['required', 'string', 'max:50', 'unique:portuario.cargo_manifest,manifest_number'],
            'manifest_date' => ['required', 'date'],
            'total_items' => ['required', 'integer', 'min:0'],
            'total_weight_kg' => ['required', 'numeric', 'min:0'],
            'document_url' => ['nullable', 'string', 'max:500', 'url'],
        ]);

        try {
            $manifest = CargoManifest::create($validated);

            $this->auditService->log(
                'CREATE',
                'CargoManifest',
                $manifest->id,
                null,
                $manifest->toArray(),
                auth()->id()
            );

            return redirect()
                ->route('cargo.manifest.show', $manifest)
                ->with('success', 'Manifiesto de carga registrado exitosamente');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar manifiesto de carga: ' . $e->getMessage()]);
        }
    }

    /**
     * Show a specific cargo manifest with its items
     * Requirements: 2.1
     */
    public function showManifest(CargoManifest $manifest): View
    {
        $manifest->load(['vesselCall.vessel', 'cargoItems.yardLocation']);
        
        return view('portuario.cargo.manifest-show', compact('manifest'));
    }

    /**
     * Show the form for creating a new cargo item
     * Requirements: 2.1
     */
    public function createCargoItem(Request $request): View
    {
        $manifestId = $request->query('manifest_id');
        $cargoItem = null;
        
        return view('portuario.cargo.item-create', compact('manifestId', 'cargoItem'));
    }

    /**
     * Store an individual cargo item
     * Requirements: 2.1
     */
    public function storeCargoItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'manifest_id' => ['required', 'exists:portuario.cargo_manifest,id'],
            'item_number' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string'],
            'cargo_type' => ['required', 'string', 'max:50', Rule::in(['CONTENEDOR', 'GRANEL', 'CARGA_GENERAL'])],
            'container_number' => ['nullable', 'string', 'max:20'],
            'seal_number' => ['nullable', 'string', 'max:50'],
            'weight_kg' => ['required', 'numeric', 'min:0'],
            'volume_m3' => ['nullable', 'numeric', 'min:0'],
            'bl_number' => ['nullable', 'string', 'max:50'],
            'consignee' => ['nullable', 'string', 'max:255'],
            'yard_location_id' => ['nullable', 'exists:portuario.yard_location,id'],
            'status' => ['required', 'string', 'max:50', Rule::in(['EN_TRANSITO', 'ALMACENADO', 'DESPACHADO'])],
        ]);

        try {
            $cargoItem = CargoItem::create($validated);

            $this->auditService->log(
                'CREATE',
                'CargoItem',
                $cargoItem->id,
                null,
                $cargoItem->toArray(),
                auth()->id()
            );

            return redirect()
                ->route('cargo.manifest.show', $validated['manifest_id'])
                ->with('success', 'Ítem de carga registrado exitosamente');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar ítem de carga: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign cargo to yard location
     * Requirements: 2.2
     */
    public function assignYardLocation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cargo_item_id' => ['required', 'exists:portuario.cargo_item,id'],
            'yard_location_id' => ['required', 'exists:portuario.yard_location,id'],
        ]);

        try {
            DB::beginTransaction();

            $cargoItem = CargoItem::findOrFail($validated['cargo_item_id']);
            $yardLocation = YardLocation::findOrFail($validated['yard_location_id']);

            // Check if location is available
            if ($yardLocation->occupied) {
                return back()->withErrors(['error' => 'La ubicación de patio ya está ocupada']);
            }

            // If cargo item had a previous location, free it
            if ($cargoItem->yard_location_id) {
                $previousLocation = YardLocation::find($cargoItem->yard_location_id);
                if ($previousLocation) {
                    $previousLocation->update(['occupied' => false]);
                }
            }

            // Update cargo item with new location
            $oldData = $cargoItem->toArray();
            $cargoItem->update([
                'yard_location_id' => $validated['yard_location_id'],
                'status' => 'ALMACENADO',
            ]);

            // Mark new location as occupied
            $yardLocation->update(['occupied' => true]);

            $this->auditService->log(
                'UPDATE',
                'CargoItem',
                $cargoItem->id,
                $oldData,
                $cargoItem->toArray(),
                auth()->id()
            );

            DB::commit();

            return back()->with('success', 'Ubicación de patio asignada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al asignar ubicación de patio: ' . $e->getMessage()]);
        }
    }

    /**
     * Register cargo movement
     * Requirements: 2.5
     */
    public function trackMovement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cargo_item_id' => ['required', 'exists:portuario.cargo_item,id'],
            'origin_location_id' => ['nullable', 'exists:portuario.yard_location,id'],
            'destination_location_id' => ['required', 'exists:portuario.yard_location,id'],
            'movement_type' => ['required', 'string', 'max:50', Rule::in(['TRACCION', 'TRANSFERENCIA', 'DESPACHO'])],
            'movement_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $cargoItem = CargoItem::findOrFail($validated['cargo_item_id']);
            $destinationLocation = YardLocation::findOrFail($validated['destination_location_id']);

            // Check if destination is available
            if ($destinationLocation->occupied) {
                return back()->withErrors(['error' => 'La ubicación de destino ya está ocupada']);
            }

            // Free origin location if specified
            if ($validated['origin_location_id']) {
                $originLocation = YardLocation::find($validated['origin_location_id']);
                if ($originLocation) {
                    $originLocation->update(['occupied' => false]);
                }
            } elseif ($cargoItem->yard_location_id) {
                // Free current location if no origin specified
                $currentLocation = YardLocation::find($cargoItem->yard_location_id);
                if ($currentLocation) {
                    $currentLocation->update(['occupied' => false]);
                }
            }

            // Update cargo item location
            $oldData = $cargoItem->toArray();
            $cargoItem->update([
                'yard_location_id' => $validated['destination_location_id'],
            ]);

            // Mark destination as occupied
            $destinationLocation->update(['occupied' => true]);

            // Log the movement in audit
            $this->auditService->log(
                'UPDATE',
                'CargoItem',
                $cargoItem->id,
                $oldData,
                array_merge($cargoItem->toArray(), [
                    'movement_type' => $validated['movement_type'],
                    'movement_date' => $validated['movement_date'],
                    'notes' => $validated['notes'] ?? null,
                ]),
                auth()->id()
            );

            DB::commit();

            return back()->with('success', 'Movimiento de carga registrado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al registrar movimiento de carga: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate operation report (COARRI/CODECO) on operation completion
     * Sends reports to mock external endpoints
     * Requirements: 2.7
     */
    public function generateOperationReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vessel_call_id' => ['required', 'integer', 'min:1'],
            'report_type' => ['required', 'string', Rule::in(['COARRI', 'CODECO'])],
            'format' => ['nullable', 'string', Rule::in(['json', 'xml'])],
        ]);

        $vesselCallId = $validated['vessel_call_id'];
        $reportType = $validated['report_type'];
        $format = $validated['format'] ?? 'json';

        try {
            // Generate the appropriate report
            $report = $reportType === 'COARRI'
                ? $this->reportGenerationService->generateCoarriReport($vesselCallId, $format)
                : $this->reportGenerationService->generateCodecoReport($vesselCallId, $format);

            // Send to mock external endpoints
            $this->sendToExternalSystems($report, $reportType);

            // Log the report generation
            $this->auditService->log(
                'CREATE',
                'OperationReport',
                "vessel_call_{$vesselCallId}",
                null,
                [
                    'report_type' => $reportType,
                    'format' => $format,
                    'vessel_call_id' => $vesselCallId,
                    'generated_at' => $report['generated_at'],
                ],
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => "Reporte {$reportType} generado exitosamente",
                'report' => $report,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error generando reporte {$reportType}", [
                'vessel_call_id' => $vesselCallId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send report to mock external systems
     * In production, this would send to actual maritime agency and warehouse systems
     *
     * @param array{format: string, content: string, vessel_call_id: int, generated_at: string} $report
     * @param string $reportType
     * @return void
     */
    private function sendToExternalSystems(array $report, string $reportType): void
    {
        // Mock external endpoints
        $endpoints = [
            'maritime_agency' => 'https://mock-api.example.com/maritime-agency/reports',
            'warehouse_system' => 'https://mock-api.example.com/warehouse/cargo-status',
        ];

        // Store mock transmission log
        $logPath = storage_path('app/mocks/operation_reports.json');
        
        // Ensure directory exists
        if (!file_exists(dirname($logPath))) {
            mkdir(dirname($logPath), 0755, true);
        }

        // Load existing logs
        $logs = [];
        if (file_exists($logPath)) {
            $logs = json_decode(file_get_contents($logPath), true) ?? [];
        }

        // Add new transmission log
        $logs[] = [
            'report_type' => $reportType,
            'vessel_call_id' => $report['vessel_call_id'],
            'format' => $report['format'],
            'generated_at' => $report['generated_at'],
            'transmitted_at' => now()->toIso8601String(),
            'endpoints' => $endpoints,
            'status' => 'MOCK_SUCCESS',
            'content_preview' => substr($report['content'], 0, 200) . '...',
        ];

        // Save logs
        file_put_contents($logPath, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Log::info("Reporte {$reportType} enviado a sistemas externos (MOCK)", [
            'vessel_call_id' => $report['vessel_call_id'],
            'endpoints' => array_keys($endpoints),
        ]);
    }

    /**
     * Get transmission log for display in UI
     */
    public function getTransmissionLog(): JsonResponse
    {
        try {
            $logPath = storage_path('app/mocks/operation_reports.json');
            
            if (!file_exists($logPath)) {
                return response()->json([
                    'success' => true,
                    'logs' => [],
                    'message' => 'No hay transmisiones registradas',
                ], 200);
            }
            
            $content = file_get_contents($logPath);
            $logs = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'logs' => [],
                    'message' => 'Error al decodificar el log: ' . json_last_error_msg(),
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'logs' => $logs ?? [],
                'total' => count($logs ?? []),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener transmission log', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'logs' => [],
                'message' => 'Error al obtener el log: ' . $e->getMessage(),
            ], 500);
        }
    }

}
