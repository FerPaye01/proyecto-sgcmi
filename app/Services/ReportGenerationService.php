<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CargoItem;
use App\Models\CargoManifest;
use App\Models\VesselCall;
use Illuminate\Support\Collection;

/**
 * Service for generating COARRI and CODECO reports
 * These reports are used to communicate cargo status to external systems
 * like maritime agencies and warehouses
 */
class ReportGenerationService
{
    /**
     * Generate COARRI (Discharge Report) for a vessel call
     * COARRI reports detail cargo discharged from a vessel
     * 
     * Requirements: 2.7
     *
     * @param int $vesselCallId
     * @param string $format 'json' or 'xml'
     * @return array{format: string, content: string, vessel_call_id: int, generated_at: string}
     */
    public function generateCoarriReport(int $vesselCallId, string $format = 'json'): array
    {
        $vesselCall = VesselCall::with(['vessel', 'berth'])->findOrFail($vesselCallId);
        
        // Get all cargo manifests for this vessel call
        $manifests = CargoManifest::with(['cargoItems.yardLocation'])
            ->where('vessel_call_id', $vesselCallId)
            ->get();

        // Build report data structure
        $reportData = [
            'report_type' => 'COARRI',
            'report_id' => 'COARRI-' . $vesselCallId . '-' . now()->format('YmdHis'),
            'generated_at' => now()->toIso8601String(),
            'vessel_call' => [
                'id' => $vesselCall->id,
                'vessel_name' => $vesselCall->vessel->name ?? 'Unknown',
                'imo_number' => $vesselCall->vessel->imo_number ?? null,
                'voyage_number' => $vesselCall->voyage_number ?? null,
                'berth' => $vesselCall->berth->name ?? null,
                'ata' => $vesselCall->ata?->toIso8601String(),
                'atb' => $vesselCall->atb?->toIso8601String(),
                'atd' => $vesselCall->atd?->toIso8601String(),
            ],
            'discharge_summary' => [
                'total_manifests' => $manifests->count(),
                'total_items' => $manifests->sum('total_items'),
                'total_weight_kg' => $manifests->sum('total_weight_kg'),
            ],
            'manifests' => $manifests->map(function ($manifest) {
                return [
                    'manifest_number' => $manifest->manifest_number,
                    'manifest_date' => $manifest->manifest_date->toDateString(),
                    'total_items' => $manifest->total_items,
                    'total_weight_kg' => (float) $manifest->total_weight_kg,
                    'items' => $manifest->cargoItems->map(function ($item) {
                        return [
                            'item_number' => $item->item_number,
                            'description' => $item->description,
                            'cargo_type' => $item->cargo_type,
                            'container_number' => $item->container_number,
                            'seal_number' => $item->seal_number,
                            'weight_kg' => (float) $item->weight_kg,
                            'volume_m3' => $item->volume_m3 ? (float) $item->volume_m3 : null,
                            'bl_number' => $item->bl_number,
                            'consignee' => $item->consignee,
                            'status' => $item->status,
                            'yard_location' => $item->yardLocation ? [
                                'zone' => $item->yardLocation->zone_code,
                                'block' => $item->yardLocation->block_code,
                                'row' => $item->yardLocation->row_code,
                                'tier' => $item->yardLocation->tier,
                            ] : null,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];

        // Format output
        $content = $format === 'xml' 
            ? $this->convertToXml($reportData, 'coarri_report')
            : json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return [
            'format' => $format,
            'content' => $content,
            'vessel_call_id' => $vesselCallId,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate CODECO (Container Status Report) for a vessel call
     * CODECO reports detail container status and movements
     * 
     * Requirements: 2.7
     *
     * @param int $vesselCallId
     * @param string $format 'json' or 'xml'
     * @return array{format: string, content: string, vessel_call_id: int, generated_at: string}
     */
    public function generateCodecoReport(int $vesselCallId, string $format = 'json'): array
    {
        $vesselCall = VesselCall::with(['vessel', 'berth'])->findOrFail($vesselCallId);
        
        // Get all container cargo items for this vessel call
        $containers = CargoItem::with(['manifest', 'yardLocation'])
            ->whereHas('manifest', function ($query) use ($vesselCallId) {
                $query->where('vessel_call_id', $vesselCallId);
            })
            ->where('cargo_type', 'CONTENEDOR')
            ->get();

        // Build report data structure
        $reportData = [
            'report_type' => 'CODECO',
            'report_id' => 'CODECO-' . $vesselCallId . '-' . now()->format('YmdHis'),
            'generated_at' => now()->toIso8601String(),
            'vessel_call' => [
                'id' => $vesselCall->id,
                'vessel_name' => $vesselCall->vessel->name ?? 'Unknown',
                'imo_number' => $vesselCall->vessel->imo_number ?? null,
                'voyage_number' => $vesselCall->voyage_number ?? null,
                'berth' => $vesselCall->berth->name ?? null,
                'ata' => $vesselCall->ata?->toIso8601String(),
                'atb' => $vesselCall->atb?->toIso8601String(),
                'atd' => $vesselCall->atd?->toIso8601String(),
            ],
            'container_summary' => [
                'total_containers' => $containers->count(),
                'by_status' => [
                    'EN_TRANSITO' => $containers->where('status', 'EN_TRANSITO')->count(),
                    'ALMACENADO' => $containers->where('status', 'ALMACENADO')->count(),
                    'DESPACHADO' => $containers->where('status', 'DESPACHADO')->count(),
                ],
                'total_weight_kg' => $containers->sum('weight_kg'),
            ],
            'containers' => $containers->map(function ($container) {
                return [
                    'container_number' => $container->container_number,
                    'seal_number' => $container->seal_number,
                    'status' => $container->status,
                    'weight_kg' => (float) $container->weight_kg,
                    'volume_m3' => $container->volume_m3 ? (float) $container->volume_m3 : null,
                    'bl_number' => $container->bl_number,
                    'consignee' => $container->consignee,
                    'manifest_number' => $container->manifest->manifest_number ?? null,
                    'yard_location' => $container->yardLocation ? [
                        'zone' => $container->yardLocation->zone_code,
                        'block' => $container->yardLocation->block_code,
                        'row' => $container->yardLocation->row_code,
                        'tier' => $container->yardLocation->tier,
                        'location_type' => $container->yardLocation->location_type,
                    ] : null,
                    'last_updated' => $container->updated_at->toIso8601String(),
                ];
            })->toArray(),
        ];

        // Format output
        $content = $format === 'xml' 
            ? $this->convertToXml($reportData, 'codeco_report')
            : json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return [
            'format' => $format,
            'content' => $content,
            'vessel_call_id' => $vesselCallId,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Convert array data to XML format
     *
     * @param array<string, mixed> $data
     * @param string $rootElement
     * @return string
     */
    private function convertToXml(array $data, string $rootElement): string
    {
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><{$rootElement}></{$rootElement}>");
        $this->arrayToXml($data, $xml);
        
        // Format XML with indentation
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        
        return $dom->saveXML();
    }

    /**
     * Recursively convert array to XML
     *
     * @param array<string, mixed> $data
     * @param \SimpleXMLElement $xml
     * @return void
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            // Handle numeric keys
            if (is_numeric($key)) {
                $key = 'item';
            }
            
            if (is_array($value)) {
                $subnode = $xml->addChild((string) $key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild((string) $key, htmlspecialchars((string) ($value ?? '')));
            }
        }
    }
}
