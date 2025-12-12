<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\OcrLprService;
use Tests\TestCase;

class OcrLprServiceTest extends TestCase
{
    private OcrLprService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OcrLprService();
    }

    public function test_process_plate_image_returns_valid_structure(): void
    {
        $result = $this->service->processPlateImage();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('plate', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('source', $result);
        
        $this->assertIsString($result['plate']);
        $this->assertIsFloat($result['confidence']);
        $this->assertIsString($result['timestamp']);
        $this->assertEquals('OCR_LPR_MOCK', $result['source']);
    }

    public function test_process_plate_image_returns_confidence_in_valid_range(): void
    {
        $result = $this->service->processPlateImage();

        $this->assertGreaterThanOrEqual(85.0, $result['confidence']);
        $this->assertLessThanOrEqual(99.0, $result['confidence']);
    }

    public function test_process_container_image_returns_valid_structure(): void
    {
        $result = $this->service->processContainerImage();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('container_number', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('iso_valid', $result);
        $this->assertArrayHasKey('source', $result);
        
        $this->assertIsString($result['container_number']);
        $this->assertIsFloat($result['confidence']);
        $this->assertIsString($result['timestamp']);
        $this->assertIsBool($result['iso_valid']);
        $this->assertEquals('OCR_CONTAINER_MOCK', $result['source']);
    }

    public function test_process_container_image_returns_valid_container_format(): void
    {
        $result = $this->service->processContainerImage();

        // Container number should be 11 characters (4 letters + 6 digits + 1 check digit)
        $this->assertEquals(11, strlen($result['container_number']));
        
        // First 4 characters should be letters
        $this->assertMatchesRegularExpression('/^[A-Z]{4}/', $result['container_number']);
        
        // Last 7 characters should be digits
        $this->assertMatchesRegularExpression('/[0-9]{7}$/', $result['container_number']);
    }

    public function test_process_container_image_returns_confidence_in_valid_range(): void
    {
        $result = $this->service->processContainerImage();

        $this->assertGreaterThanOrEqual(80.0, $result['confidence']);
        $this->assertLessThanOrEqual(98.0, $result['confidence']);
    }

    public function test_process_both_images_returns_both_results(): void
    {
        $result = $this->service->processBothImages();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('plate', $result);
        $this->assertArrayHasKey('container', $result);
        
        $this->assertArrayHasKey('plate', $result['plate']);
        $this->assertArrayHasKey('confidence', $result['plate']);
        
        $this->assertArrayHasKey('container_number', $result['container']);
        $this->assertArrayHasKey('confidence', $result['container']);
    }

    public function test_is_confidence_acceptable_with_default_threshold(): void
    {
        $this->assertTrue($this->service->isConfidenceAcceptable(90.0));
        $this->assertTrue($this->service->isConfidenceAcceptable(85.0));
        $this->assertFalse($this->service->isConfidenceAcceptable(84.9));
        $this->assertFalse($this->service->isConfidenceAcceptable(50.0));
    }

    public function test_is_confidence_acceptable_with_custom_threshold(): void
    {
        $this->assertTrue($this->service->isConfidenceAcceptable(95.0, 90.0));
        $this->assertTrue($this->service->isConfidenceAcceptable(90.0, 90.0));
        $this->assertFalse($this->service->isConfidenceAcceptable(89.9, 90.0));
        $this->assertFalse($this->service->isConfidenceAcceptable(70.0, 90.0));
    }

    public function test_multiple_calls_return_different_plates(): void
    {
        $plates = [];
        
        // Call multiple times to ensure randomness
        for ($i = 0; $i < 10; $i++) {
            $result = $this->service->processPlateImage();
            $plates[] = $result['plate'];
        }
        
        // Should have at least 2 different plates in 10 calls
        $uniquePlates = array_unique($plates);
        $this->assertGreaterThanOrEqual(2, count($uniquePlates));
    }

    public function test_multiple_calls_return_different_containers(): void
    {
        $containers = [];
        
        // Call multiple times to ensure randomness
        for ($i = 0; $i < 10; $i++) {
            $result = $this->service->processContainerImage();
            $containers[] = $result['container_number'];
        }
        
        // Should have at least 2 different containers in 10 calls
        $uniqueContainers = array_unique($containers);
        $this->assertGreaterThanOrEqual(2, count($uniqueContainers));
    }
}
