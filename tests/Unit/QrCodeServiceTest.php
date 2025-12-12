<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\QrCodeService;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    protected QrCodeService $qrService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qrService = new QrCodeService();
    }

    public function test_generates_unique_pass_code_with_correct_format(): void
    {
        $existingCodes = [];
        $callback = function ($code) use (&$existingCodes) {
            return in_array($code, $existingCodes);
        };

        $code = $this->qrService->generateUniquePassCode($callback);

        // Check format: DP-XXXXXXXXXX
        $this->assertMatchesRegularExpression('/^DP-[A-Z0-9]{10}$/', $code);
        $this->assertTrue($this->qrService->isValidPassCodeFormat($code));
    }

    public function test_generates_different_pass_codes(): void
    {
        $codes = [];
        $callback = fn($code) => in_array($code, $codes);

        for ($i = 0; $i < 10; $i++) {
            $code = $this->qrService->generateUniquePassCode($callback);
            $this->assertNotContains($code, $codes);
            $codes[] = $code;
        }

        // All codes should be unique
        $this->assertCount(10, array_unique($codes));
    }

    public function test_generates_qr_code_as_base64(): void
    {
        $passCode = 'DP-TEST12345';
        $qrCode = $this->qrService->generateQrCode($passCode);

        // Should be a valid base64 string
        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        
        // Should be decodable
        $decoded = base64_decode($qrCode, true);
        $this->assertNotFalse($decoded);
        
        // Should start with PNG signature
        $this->assertStringStartsWith("\x89PNG", $decoded);
    }

    public function test_generates_qr_code_with_additional_data(): void
    {
        $passCode = 'DP-TEST12345';
        $additionalData = [
            'holder' => 'Juan Pérez',
            'type' => 'VEHICULAR',
        ];

        $qrCode = $this->qrService->generateQrCode($passCode, $additionalData);

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
    }

    public function test_generates_qr_code_with_full_pass_info(): void
    {
        $passData = [
            'pass_code' => 'DP-TEST12345',
            'pass_type' => 'VEHICULAR',
            'holder_name' => 'Juan Pérez',
            'holder_dni' => '12345678',
            'truck_placa' => 'ABC-123',
            'valid_from' => '2024-01-01T00:00:00Z',
            'valid_until' => '2024-12-31T23:59:59Z',
        ];

        $qrCode = $this->qrService->generateQrCodeWithPassInfo($passData);

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        
        // Verify it's a valid PNG
        $decoded = base64_decode($qrCode, true);
        $this->assertStringStartsWith("\x89PNG", $decoded);
    }

    public function test_throws_exception_for_missing_required_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field');

        $incompleteData = [
            'pass_code' => 'DP-TEST12345',
            'pass_type' => 'PERSONAL',
            // Missing holder_name, valid_from, valid_until
        ];

        $this->qrService->generateQrCodeWithPassInfo($incompleteData);
    }

    public function test_validates_pass_code_format(): void
    {
        // Valid formats
        $this->assertTrue($this->qrService->isValidPassCodeFormat('DP-ABC1234567'));
        $this->assertTrue($this->qrService->isValidPassCodeFormat('DP-1234567890'));
        $this->assertTrue($this->qrService->isValidPassCodeFormat('DP-ABCDEFGHIJ'));

        // Invalid formats
        $this->assertFalse($this->qrService->isValidPassCodeFormat('DP-ABC123')); // Too short
        $this->assertFalse($this->qrService->isValidPassCodeFormat('DP-ABC123456789')); // Too long
        $this->assertFalse($this->qrService->isValidPassCodeFormat('ABC-1234567890')); // Wrong prefix
        $this->assertFalse($this->qrService->isValidPassCodeFormat('DP-abc1234567')); // Lowercase
        $this->assertFalse($this->qrService->isValidPassCodeFormat('DP-ABC123456@')); // Special char
    }

    public function test_decodes_qr_data(): void
    {
        $originalData = [
            'pass_code' => 'DP-TEST12345',
            'holder' => 'Juan Pérez',
            'type' => 'VEHICULAR',
        ];

        $jsonData = json_encode($originalData);
        $decoded = $this->qrService->decodeQrData($jsonData);

        $this->assertEquals($originalData, $decoded);
    }

    public function test_throws_exception_for_invalid_json(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid QR data format');

        $this->qrService->decodeQrData('invalid json data');
    }

    public function test_generates_validation_qr_code(): void
    {
        $passCode = 'DP-TEST12345';
        $qrCode = $this->qrService->generateValidationQrCode($passCode);

        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
        
        // Should be a valid PNG
        $decoded = base64_decode($qrCode, true);
        $this->assertStringStartsWith("\x89PNG", $decoded);
    }

    public function test_validation_qr_code_is_smaller_than_regular(): void
    {
        $passCode = 'DP-TEST12345';
        
        $regularQr = $this->qrService->generateQrCode($passCode);
        $validationQr = $this->qrService->generateValidationQrCode($passCode);

        // Validation QR should be smaller (200px vs 300px)
        $this->assertLessThan(strlen($regularQr), strlen($validationQr));
    }
}
