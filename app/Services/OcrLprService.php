<?php

declare(strict_types=1);

namespace App\Services;

/**
 * OCR/LPR Service - Mock Implementation
 * 
 * Simulates Optical Character Recognition (OCR) and License Plate Recognition (LPR)
 * for automatic reading of license plates and container numbers at gate entry points.
 * 
 * In production, this would integrate with actual OCR/LPR hardware/software.
 */
class OcrLprService
{
    /**
     * Process plate image and return recognized plate number with confidence score
     * 
     * @param string|null $imagePath Path to the image file (unused in mock)
     * @return array{plate: string, confidence: float, timestamp: string}
     */
    public function processPlateImage(?string $imagePath = null): array
    {
        // Mock implementation - simulates plate recognition
        // In production, this would call actual OCR/LPR API or hardware
        
        $mockPlates = [
            'ABC-123',
            'XYZ-789',
            'DEF-456',
            'GHI-012',
            'JKL-345',
            'MNO-678',
            'PQR-901',
            'STU-234',
        ];
        
        $plate = $mockPlates[array_rand($mockPlates)];
        
        // Simulate varying confidence levels (85% - 99%)
        $confidence = round(mt_rand(8500, 9900) / 100, 2);
        
        return [
            'plate' => $plate,
            'confidence' => $confidence,
            'timestamp' => now()->toIso8601String(),
            'source' => 'OCR_LPR_MOCK',
        ];
    }
    
    /**
     * Process container image and return recognized container number with confidence score
     * 
     * @param string|null $imagePath Path to the image file (unused in mock)
     * @return array{container_number: string, confidence: float, timestamp: string, iso_valid: bool}
     */
    public function processContainerImage(?string $imagePath = null): array
    {
        // Mock implementation - simulates container number recognition
        // In production, this would call actual OCR API or hardware
        
        // Generate mock container numbers following ISO 6346 format
        // Format: 4 letters (owner code) + 6 digits + 1 check digit
        $ownerCodes = ['MSCU', 'MAEU', 'CSQU', 'TEMU', 'CMAU', 'HLCU', 'OOLU', 'APZU'];
        $ownerCode = $ownerCodes[array_rand($ownerCodes)];
        
        // Generate 6 random digits
        $serialNumber = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Calculate check digit (simplified - not actual ISO 6346 algorithm)
        $checkDigit = mt_rand(0, 9);
        
        $containerNumber = $ownerCode . $serialNumber . $checkDigit;
        
        // Simulate varying confidence levels (80% - 98%)
        $confidence = round(mt_rand(8000, 9800) / 100, 2);
        
        // Simulate ISO validation (most should be valid)
        $isoValid = mt_rand(1, 100) > 10; // 90% valid
        
        return [
            'container_number' => $containerNumber,
            'confidence' => $confidence,
            'timestamp' => now()->toIso8601String(),
            'iso_valid' => $isoValid,
            'source' => 'OCR_CONTAINER_MOCK',
        ];
    }
    
    /**
     * Process both plate and container images in a single call
     * Useful for gate events where both need to be captured
     * 
     * @param string|null $plateImagePath Path to plate image (unused in mock)
     * @param string|null $containerImagePath Path to container image (unused in mock)
     * @return array{plate: array, container: array}
     */
    public function processBothImages(?string $plateImagePath = null, ?string $containerImagePath = null): array
    {
        return [
            'plate' => $this->processPlateImage($plateImagePath),
            'container' => $this->processContainerImage($containerImagePath),
        ];
    }
    
    /**
     * Validate if confidence score meets minimum threshold
     * 
     * @param float $confidence Confidence score (0-100)
     * @param float $threshold Minimum acceptable confidence (default 85%)
     * @return bool
     */
    public function isConfidenceAcceptable(float $confidence, float $threshold = 85.0): bool
    {
        return $confidence >= $threshold;
    }
}
