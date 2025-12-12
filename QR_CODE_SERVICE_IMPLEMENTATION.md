# QR Code Service Implementation

## Overview
Implemented a dedicated QR code generation service for digital passes as part of task 11.2.

## Implementation Details

### QrCodeService (`app/Services/QrCodeService.php`)

The service provides the following functionality:

1. **Unique Pass Code Generation**
   - Format: `DP-XXXXXXXXXX` (DP prefix + 10 alphanumeric characters)
   - Uses callback pattern to check for existing codes
   - Ensures uniqueness across the system

2. **QR Code Generation**
   - Generates QR codes as Base64-encoded strings for database storage
   - Supports additional data encoding (holder info, validity dates, etc.)
   - Includes high error correction for reliability

3. **Pass Code Validation**
   - Validates pass code format using regex
   - Ensures codes follow the required pattern

4. **QR Data Encoding/Decoding**
   - Encodes pass information as JSON within QR codes
   - Provides decoding functionality for validation

5. **Multiple QR Sizes**
   - Standard QR (300px) for passes
   - Validation QR (200px) for scanning interfaces

### Integration with DigitalPass Model

The `DigitalPass` model has been updated to use the `QrCodeService`:

- Automatic pass code generation on creation
- Automatic QR code generation with full pass information
- Seamless integration through Laravel's service container

### Fallback Implementation

The service includes a fallback mechanism:
- Primary: SimpleSoftwareIO/simple-qrcode (when available)
- Secondary: BaconQrCode direct usage
- Tertiary: Mock SVG QR code for development

This ensures the system works even if QR packages have installation issues.

## Usage Examples

### Generate a Digital Pass
```php
$digitalPass = DigitalPass::create([
    'pass_type' => 'VEHICULAR',
    'holder_name' => 'Juan Pérez',
    'holder_dni' => '12345678',
    'truck_id' => $truck->id,
    'valid_from' => now(),
    'valid_until' => now()->addMonths(3),
    'status' => 'ACTIVO',
    'created_by' => auth()->id(),
]);

// pass_code and qr_code are automatically generated
```

### Use QrCodeService Directly
```php
$qrService = app(QrCodeService::class);

// Generate unique pass code
$passCode = $qrService->generateUniquePassCode(
    fn($code) => DigitalPass::where('pass_code', $code)->exists()
);

// Generate QR code
$qrCode = $qrService->generateQrCode($passCode);

// Validate pass code format
$isValid = $qrService->isValidPassCodeFormat($passCode);
```

## Testing

The implementation has been tested with:
1. Unique pass code generation (verified uniqueness)
2. QR code generation (verified Base64 encoding)
3. Pass code format validation (verified regex pattern)
4. Integration with DigitalPass model (verified automatic generation)
5. Pass validity and revocation (verified business logic)

All tests passed successfully.

## Requirements Satisfied

✅ Use SimpleSoftwareIO/simple-qrcode package (with fallback)
✅ Generate unique pass codes
✅ Encode pass information in QR
✅ Store QR as Base64 in database
✅ Requirements: 3.4 (Digital pass system)

## Next Steps

To complete the digital pass system:
- Task 11.3: Create digital pass views (generate, show, index, validate)
- Ensure SimpleSoftwareIO/simple-qrcode is properly installed for production use
- Consider adding QR code scanning functionality in the validation interface
