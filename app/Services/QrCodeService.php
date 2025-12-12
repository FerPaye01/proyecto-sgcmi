<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service for generating QR codes for digital passes
 * 
 * This service handles:
 * - Generation of unique pass codes
 * - QR code generation with pass information
 * - Base64 encoding for database storage
 * 
 * Requirements: SimpleSoftwareIO/simple-qrcode package
 * Install with: composer require simplesoftwareio/simple-qrcode
 */
class QrCodeService
{
    /**
     * Generate a unique pass code
     * 
     * Format: DP-XXXXXXXXXX (DP prefix + 10 alphanumeric characters)
     * 
     * @param callable $existsCallback Callback to check if code already exists
     * @return string Unique pass code
     */
    public function generateUniquePassCode(callable $existsCallback): string
    {
        do {
            $code = 'DP-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 10));
        } while ($existsCallback($code));

        return $code;
    }

    /**
     * Generate QR code as Base64 string
     * 
     * Encodes pass information into a QR code and returns it as Base64
     * for storage in the database.
     * 
     * @param string $passCode The pass code to encode
     * @param array $additionalData Additional data to encode (optional)
     * @return string Base64 encoded QR code image
     */
    public function generateQrCode(string $passCode, array $additionalData = []): string
    {
        // Prepare data to encode
        $data = $this->prepareQrData($passCode, $additionalData);
        
        // Check if SimpleSoftwareIO QrCode is available
        if (class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
            // Use Laravel Facade
            return base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(300)
                    ->errorCorrection('H') // High error correction
                    ->generate($data)
            );
        } elseif (class_exists('\BaconQrCode\Writer')) {
            // Fallback to BaconQrCode directly
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(300),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $writer = new \BaconQrCode\Writer($renderer);
            $qrCodeSvg = $writer->writeString($data);
            
            return base64_encode($qrCodeSvg);
        } else {
            // Fallback: generate a mock QR code for development
            // This should be replaced with actual QR generation in production
            return base64_encode($this->generateMockQrCode($data));
        }
    }

    /**
     * Prepare data for QR code encoding
     * 
     * @param string $passCode The pass code
     * @param array $additionalData Additional data to include
     * @return string JSON encoded data
     */
    protected function prepareQrData(string $passCode, array $additionalData = []): string
    {
        $data = array_merge([
            'pass_code' => $passCode,
            'generated_at' => now()->toIso8601String(),
        ], $additionalData);

        return json_encode($data);
    }

    /**
     * Generate QR code with full pass information
     * 
     * @param array $passData Complete pass data including holder info, validity, etc.
     * @return string Base64 encoded QR code image
     */
    public function generateQrCodeWithPassInfo(array $passData): string
    {
        // Validate required fields
        $requiredFields = ['pass_code', 'pass_type', 'holder_name', 'valid_from', 'valid_until'];
        foreach ($requiredFields as $field) {
            if (!isset($passData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Prepare comprehensive data for QR
        $qrData = [
            'pass_code' => $passData['pass_code'],
            'type' => $passData['pass_type'],
            'holder' => $passData['holder_name'],
            'dni' => $passData['holder_dni'] ?? null,
            'truck' => $passData['truck_placa'] ?? null,
            'valid_from' => $passData['valid_from'],
            'valid_until' => $passData['valid_until'],
        ];

        // Remove null values
        $qrData = array_filter($qrData, fn($value) => $value !== null);

        return $this->generateQrCode($passData['pass_code'], $qrData);
    }

    /**
     * Decode QR data from JSON string
     * 
     * @param string $qrData JSON encoded QR data
     * @return array Decoded data
     */
    public function decodeQrData(string $qrData): array
    {
        $decoded = json_decode($qrData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid QR data format');
        }

        return $decoded;
    }

    /**
     * Validate pass code format
     * 
     * @param string $passCode Pass code to validate
     * @return bool True if valid format
     */
    public function isValidPassCodeFormat(string $passCode): bool
    {
        // Format: DP-XXXXXXXXXX (DP prefix + 10 alphanumeric characters)
        return preg_match('/^DP-[A-Z0-9]{10}$/', $passCode) === 1;
    }

    /**
     * Generate QR code for validation/scanning interface
     * 
     * Returns a smaller QR code suitable for display in validation interfaces
     * 
     * @param string $passCode The pass code to encode
     * @return string Base64 encoded QR code image
     */
    public function generateValidationQrCode(string $passCode): string
    {
        // Check if SimpleSoftwareIO QrCode is available
        if (class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
            return base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(200)
                    ->errorCorrection('M') // Medium error correction
                    ->generate($passCode)
            );
        } elseif (class_exists('\BaconQrCode\Writer')) {
            // Fallback to BaconQrCode directly
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $writer = new \BaconQrCode\Writer($renderer);
            $qrCodeSvg = $writer->writeString($passCode);
            
            return base64_encode($qrCodeSvg);
        } else {
            // Fallback: generate a mock QR code for development
            return base64_encode($this->generateMockQrCode($passCode));
        }
    }

    /**
     * Generate a mock QR code for development/testing
     * This is a fallback when QR libraries are not available
     * 
     * @param string $data Data to encode
     * @return string Mock QR code data
     */
    protected function generateMockQrCode(string $data): string
    {
        // Create a simple SVG placeholder
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300">
    <rect width="300" height="300" fill="white"/>
    <text x="150" y="150" text-anchor="middle" font-family="monospace" font-size="12">
        QR Code: {$data}
    </text>
</svg>
SVG;
        
        return $svg;
    }
}
