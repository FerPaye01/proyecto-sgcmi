# Audit Implementation

## Overview

The audit system logs all Create, Update, and Delete (CUD) operations in the `audit.audit_log` table. This provides a complete audit trail for compliance and debugging purposes.

## Components

### 1. AuditLog Model (`app/Models/AuditLog.php`)

Eloquent model for the `audit.audit_log` table with the following fields:
- `event_ts`: Timestamp of the event
- `actor_user`: User ID who performed the action
- `action`: Type of action (CREATE, UPDATE, DELETE, VIEW, EXPORT)
- `object_schema`: Database schema (e.g., 'portuario', 'terrestre', 'aduanas')
- `object_table`: Database table name
- `object_id`: ID of the affected record
- `details`: JSON field with additional information

### 2. AuditService (`app/Services/AuditService.php`)

Service class that handles audit logging with the following features:
- `log()`: Main method to create audit log entries
- `sanitizeDetails()`: Automatically masks PII fields (placa, tramite_ext_id, password, token, secret)

### 3. Controller Integration

The `VesselCallController` demonstrates how to integrate audit logging:

```php
// In constructor
public function __construct(
    private AuditService $auditService
) {}

// On CREATE
$this->auditService->log(
    action: 'CREATE',
    objectSchema: 'portuario',
    objectTable: 'vessel_call',
    objectId: $vesselCall->id,
    details: [...]
);

// On UPDATE
$this->auditService->log(
    action: 'UPDATE',
    objectSchema: 'portuario',
    objectTable: 'vessel_call',
    objectId: $vesselCall->id,
    details: [
        'old' => $oldData,
        'new' => $newData,
    ]
);

// On DELETE
$this->auditService->log(
    action: 'DELETE',
    objectSchema: 'portuario',
    objectTable: 'vessel_call',
    objectId: $vesselCallId,
    details: $vesselCallData
);
```

## Usage in Other Controllers

To add audit logging to other controllers:

1. Inject `AuditService` in the constructor:
```php
public function __construct(
    private AuditService $auditService
) {}
```

2. Call `log()` after each CUD operation:
```php
$this->auditService->log(
    action: 'CREATE|UPDATE|DELETE',
    objectSchema: 'schema_name',
    objectTable: 'table_name',
    objectId: $recordId,
    details: ['key' => 'value']
);
```

## PII Protection

The `AuditService` automatically masks the following PII fields:
- `placa` (truck license plates)
- `tramite_ext_id` (customs transaction IDs)
- `password`
- `token`
- `secret`

These fields will be replaced with `***MASKED***` in the audit log.

## Testing

Tests are located in `tests/Feature/AuditLogTest.php` and verify:
- Audit logs are created on CREATE operations
- Audit logs are created on UPDATE operations with old/new values
- Audit logs are created on DELETE operations
- PII fields are properly sanitized

Run tests with:
```bash
php artisan test --filter=AuditLogTest
```

## Database Schema

The audit log table structure:
```sql
CREATE TABLE audit.audit_log (
    id BIGSERIAL PRIMARY KEY,
    event_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actor_user VARCHAR(255) NULLABLE,
    action VARCHAR(50),
    object_schema VARCHAR(50) NULLABLE,
    object_table VARCHAR(50) NULLABLE,
    object_id BIGINT NULLABLE,
    details JSON NULLABLE
);
```

## Next Steps

To complete the audit implementation across the system:
1. Add audit logging to `AppointmentController`
2. Add audit logging to `TramiteController`
3. Add audit logging to `GateEventController`
4. Create an admin view to query audit logs
5. Implement audit log retention policies
