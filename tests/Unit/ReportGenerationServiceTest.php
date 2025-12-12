<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\CargoItem;
use App\Models\CargoManifest;
use App\Models\VesselCall;
use App\Models\YardLocation;
use App\Services\ReportGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportGenerationService();
    }

    public function test_generate_coarri_report_json_format(): void
    {
        // Arrange
        $vesselCall = VesselCall::factory()->create();
        $manifest = CargoManifest::factory()->create([
            'vessel_call_id' => $vesselCall->id,
        ]);
        $yardLocation = YardLocation::factory()->create();
        CargoItem::factory()->count(3)->create([
            'manifest_id' => $manifest->id,
            'yard_location_id' => $yardLocation->id,
        ]);

        // Act
        $report = $this->service->generateCoarriReport($vesselCall->id, 'json');

        // Assert
        $this->assertEquals('json', $report['format']);
        $this->assertEquals($vesselCall->id, $report['vessel_call_id']);
        $this->assertNotEmpty($report['content']);
        $this->assertNotEmpty($report['generated_at']);

        // Verify JSON structure
        $data = json_decode($report['content'], true);
        $this->assertEquals('COARRI', $data['report_type']);
        $this->assertArrayHasKey('vessel_call', $data);
        $this->assertArrayHasKey('discharge_summary', $data);
        $this->assertArrayHasKey('manifests', $data);
        $this->assertCount(1, $data['manifests']);
        $this->assertCount(3, $data['manifests'][0]['items']);
    }

    public function test_generate_coarri_report_xml_format(): void
    {
        // Arrange
        $vesselCall = VesselCall::factory()->create();
        $manifest = CargoManifest::factory()->create([
            'vessel_call_id' => $vesselCall->id,
        ]);
        CargoItem::factory()->create([
            'manifest_id' => $manifest->id,
        ]);

        // Act
        $report = $this->service->generateCoarriReport($vesselCall->id, 'xml');

        // Assert
        $this->assertEquals('xml', $report['format']);
        $this->assertStringContainsString('<?xml', $report['content']);
        $this->assertStringContainsString('<coarri_report>', $report['content']);
        $this->assertStringContainsString('<report_type>COARRI</report_type>', $report['content']);
    }

    public function test_generate_codeco_report_json_format(): void
    {
        // Arrange
        $vesselCall = VesselCall::factory()->create();
        $manifest = CargoManifest::factory()->create([
            'vessel_call_id' => $vesselCall->id,
        ]);
        $yardLocation = YardLocation::factory()->create();
        
        // Create container cargo items
        CargoItem::factory()->count(2)->create([
            'manifest_id' => $manifest->id,
            'cargo_type' => 'CONTENEDOR',
            'container_number' => 'CONT' . rand(1000, 9999),
            'yard_location_id' => $yardLocation->id,
        ]);
        
        // Create non-container item (should not be included)
        CargoItem::factory()->create([
            'manifest_id' => $manifest->id,
            'cargo_type' => 'GRANEL',
        ]);

        // Act
        $report = $this->service->generateCodecoReport($vesselCall->id, 'json');

        // Assert
        $this->assertEquals('json', $report['format']);
        $this->assertEquals($vesselCall->id, $report['vessel_call_id']);
        $this->assertNotEmpty($report['content']);

        // Verify JSON structure
        $data = json_decode($report['content'], true);
        $this->assertEquals('CODECO', $data['report_type']);
        $this->assertArrayHasKey('vessel_call', $data);
        $this->assertArrayHasKey('container_summary', $data);
        $this->assertArrayHasKey('containers', $data);
        $this->assertCount(2, $data['containers']); // Only containers, not granel
        $this->assertEquals(2, $data['container_summary']['total_containers']);
    }

    public function test_generate_codeco_report_xml_format(): void
    {
        // Arrange
        $vesselCall = VesselCall::factory()->create();
        $manifest = CargoManifest::factory()->create([
            'vessel_call_id' => $vesselCall->id,
        ]);
        CargoItem::factory()->create([
            'manifest_id' => $manifest->id,
            'cargo_type' => 'CONTENEDOR',
            'container_number' => 'TEST1234',
        ]);

        // Act
        $report = $this->service->generateCodecoReport($vesselCall->id, 'xml');

        // Assert
        $this->assertEquals('xml', $report['format']);
        $this->assertStringContainsString('<?xml', $report['content']);
        $this->assertStringContainsString('<codeco_report>', $report['content']);
        $this->assertStringContainsString('<report_type>CODECO</report_type>', $report['content']);
    }

    public function test_coarri_report_includes_vessel_call_details(): void
    {
        // Arrange
        $vesselCall = VesselCall::factory()->create([
            'voyage_number' => 'VOY123',
        ]);
        CargoManifest::factory()->create([
            'vessel_call_id' => $vesselCall->id,
        ]);

        // Act
        $report = $this->service->generateCoarriReport($vesselCall->id, 'json');
        $data = json_decode($report['content'], true);

        // Assert
        $this->assertEquals($vesselCall->id, $data['vessel_call']['id']);
        $this->assertEquals('VOY123', $data['vessel_call']['voyage_number']);
        $this->assertNotNull($data['vessel_call']['vessel_name']);
    }

    public function test_codeco_report_groups_containers_by_status(): void
    {
        // Arrange
        $vesselCall = VesselCall::factory()->create();
        $manifest = CargoManifest::factory()->create([
            'vessel_call_id' => $vesselCall->id,
        ]);
        
        CargoItem::factory()->create([
            'manifest_id' => $manifest->id,
            'cargo_type' => 'CONTENEDOR',
            'status' => 'EN_TRANSITO',
        ]);
        CargoItem::factory()->count(2)->create([
            'manifest_id' => $manifest->id,
            'cargo_type' => 'CONTENEDOR',
            'status' => 'ALMACENADO',
        ]);
        CargoItem::factory()->create([
            'manifest_id' => $manifest->id,
            'cargo_type' => 'CONTENEDOR',
            'status' => 'DESPACHADO',
        ]);

        // Act
        $report = $this->service->generateCodecoReport($vesselCall->id, 'json');
        $data = json_decode($report['content'], true);

        // Assert
        $this->assertEquals(1, $data['container_summary']['by_status']['EN_TRANSITO']);
        $this->assertEquals(2, $data['container_summary']['by_status']['ALMACENADO']);
        $this->assertEquals(1, $data['container_summary']['by_status']['DESPACHADO']);
    }
}
