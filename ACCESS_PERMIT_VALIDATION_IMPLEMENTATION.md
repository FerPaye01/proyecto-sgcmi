# Access Permit Validation Implementation

## Overview

This document describes the implementation of Task 14: "Implement access permission validation" from the Operador Portuario specification.

## Requirements

**Requirement 3.6**: WHEN se validan permisos, THEN el Sistema SHALL verificar Permiso de Salida, Nota de Embarque y Autorización de Ingreso antes de permitir el acceso

## Implementation

### 1. AccessPermitController (Task 14.1)

Created `app/Http/Controllers/Terrestre/AccessPermitController.php` with the following methods:

#### Methods:

- **index()**: Lists permits by truck or cargo with filtering capabilities
- **create()**: Shows form for creating new access permits
- **store()**: Creates access permit with validation
- **validatePermits()**: Validates all required permits before access

#### Validation Logic:

The `validatePermits()` method checks:

1. **Digital pass is active** and not expired
2. **For SALIDA (Exit)**:
   - Requires valid Exit Permit (SALIDA)
   - If cargo specified: requires Booking Note (B/L)
   - Cargo must be in valid status (ALMACENADO or EN_TRANSITO)
3. **For ENTRADA (Entry)**:
   - Requires valid Access Authorization (INGRESO)

### 2. GateEventController Validation (Task 14.2)

Modified `app/Http/Controllers/GateEventController.php` to add validation before creating gate events:

#### New Methods:

- **validateAccessPermissions()**: Private method that validates all required permits
- **markPermitsAsUsed()**: Marks permits as USADO after successful gate event

#### Integration Points:

1. **store()** method: Validates permits before creating gate event
2. **processOcrLprData()** method: Validates permits for OCR/LPR flow

#### Validation Flow:

```
Gate Event Request
    ↓
Find Active Digital Pass for Truck
    ↓
Validate Based on Action (ENTRADA/SALIDA)
    ↓
Check Required Permits
    ↓
If SALIDA with Cargo: Check B/L and Cargo Status
    ↓
Return Errors or Allow Access
    ↓
Mark Permits as USADO
```

### 3. AccessPermitPolicy

Created `app/Policies/AccessPermitPolicy.php` to enforce RBAC:

- Uses existing GATE_EVENT_READ and GATE_EVENT_WRITE permissions
- Registered in AuthServiceProvider

### 4. Routes

Added routes in `routes/web.php`:

```php
Route::prefix('access-permit')->group(function () {
    Route::get('/', [AccessPermitController::class, 'index']);
    Route::get('/create', [AccessPermitController::class, 'create']);
    Route::post('/', [AccessPermitController::class, 'store']);
    Route::post('/validate', [AccessPermitController::class, 'validate']);
});
```

### 5. Views

Created Blade views:

- `terrestre/access-permit/index.blade.php`: List permits with filters
- `terrestre/access-permit/create.blade.php`: Create new permit

## Testing

Added comprehensive tests in:

### AccessControlTest.php

- `test_can_create_access_permit()`
- `test_cannot_create_permit_with_expired_digital_pass()`
- `test_access_permit_validation_requires_exit_permit_for_salida()`
- `test_access_permit_validation_passes_with_valid_exit_permit()`
- `test_access_permit_validation_requires_entry_permit_for_entrada()`

### GateEventTest.php

- `test_gate_event_validates_exit_permit_for_salida()`
- `test_gate_event_allows_salida_with_valid_exit_permit()`
- `test_gate_event_validates_entry_permit_for_entrada()`
- `test_gate_event_validates_booking_note_for_cargo_exit()`

## Usage Example

### Creating an Access Permit

```php
POST /terrestre/access-permit
{
    "digital_pass_id": 1,
    "permit_type": "SALIDA",
    "cargo_item_id": 5
}
```

### Validating Permits

```php
POST /terrestre/access-permit/validate
{
    "digital_pass_id": 1,
    "action": "SALIDA",
    "cargo_item_id": 5
}
```

Response:
```json
{
    "valid": true,
    "digital_pass": {...},
    "errors": [],
    "warnings": [],
    "timestamp": "2024-01-15T10:30:00Z"
}
```

### Creating Gate Event (with validation)

```php
POST /terrestre/gate-events
{
    "gate_id": 1,
    "truck_id": 10,
    "action": "SALIDA",
    "event_ts": "2024-01-15 10:30:00"
}
```

The system will automatically:
1. Find the truck's active digital pass
2. Validate required permits exist
3. Check cargo status and B/L if applicable
4. Create gate event if valid
5. Mark permits as USADO

## Error Messages

Spanish error messages are returned for validation failures:

- "No hay pase digital activo para este camión"
- "Falta Permiso de Salida válido"
- "Falta Nota de Embarque (B/L) para la carga"
- "La carga no está en estado válido para despacho"
- "Falta Autorización de Ingreso válida"

## Security

- All operations require authentication
- RBAC enforced via AccessPermitPolicy
- PII (truck plates) masked in audit logs
- Audit trail for all permit operations

## Database Schema

Uses existing tables:
- `terrestre.digital_pass`
- `terrestre.access_permit`
- `portuario.cargo_item`
- `terrestre.gate_event`

## Compliance

✅ Validates Requirement 3.6
✅ Follows PSR-12 coding standards
✅ Uses Spanish for all user-facing text
✅ Implements RBAC via policies
✅ Masks PII in audit logs
✅ Comprehensive test coverage
