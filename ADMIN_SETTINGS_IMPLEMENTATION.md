# Admin Settings Implementation - Threshold Configuration

## Overview
This implementation allows ADMIN users to configure alert thresholds and SLA values through a web interface. All settings are persisted in the database and cached for performance.

## Components Implemented

### 1. Database Schema
**File**: `database/migrations/2024_01_01_000007_create_analytics_tables.php`

Added new table `analytics.settings`:
```sql
CREATE TABLE analytics.settings (
    id BIGSERIAL PRIMARY KEY,
    key VARCHAR(255) UNIQUE NOT NULL,
    value VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. Setting Model
**File**: `app/Models/Setting.php`

Provides convenient methods for getting and setting threshold values:
- `Setting::getValue($key, $default)` - Retrieve a setting with optional default
- `Setting::setValue($key, $value, $description)` - Store or update a setting

### 3. Settings Controller
**File**: `app/Http/Controllers/Admin/SettingsController.php`

Two main methods:
- `showThresholds()` - Display the settings form with current values
- `updateThresholds(Request $request)` - Validate and persist threshold updates

Features:
- Validates all numeric inputs with appropriate ranges
- Stores values in database for persistence
- Caches values for 15 minutes for performance
- Creates audit log entries for all changes
- Only accessible to ADMIN users via middleware

### 4. Settings View
**File**: `resources/views/admin/settings/thresholds.blade.php`

User-friendly form with:
- Alert threshold inputs (berth utilization %, truck waiting time hours)
- SLA threshold inputs (turnaround hours, truck waiting time hours, customs dispatch hours)
- Validation error messages
- Information box explaining the settings
- Save and clear buttons

### 5. Routes
**File**: `routes/web.php`

Two routes under `/admin/settings`:
- `GET /admin/settings/thresholds` → `showThresholds()` (name: `admin.settings.thresholds.show`)
- `PATCH /admin/settings/thresholds` → `updateThresholds()` (name: `admin.settings.thresholds.update`)

Both routes require `auth` middleware and `permission:ADMIN` middleware.

### 6. Seeder
**File**: `database/seeders/AnalyticsSeeder.php`

Initializes default threshold values:
- `alert_berth_utilization`: 85%
- `alert_truck_waiting_time`: 4 hours
- `sla_turnaround`: 48 hours
- `sla_truck_waiting_time`: 2 hours
- `sla_customs_dispatch`: 24 hours

### 7. Tests
**File**: `tests/Feature/AdminSettingsTest.php`

Comprehensive test suite with 8 tests:
1. `test_admin_can_view_thresholds_settings` - Admin can access settings page
2. `test_non_admin_cannot_view_thresholds_settings` - Non-admin gets 403
3. `test_unauthenticated_user_cannot_view_thresholds_settings` - Unauthenticated redirects to login
4. `test_admin_can_update_thresholds` - Admin can update and values persist in DB
5. `test_non_admin_cannot_update_thresholds` - Non-admin gets 403
6. `test_threshold_validation_fails_with_invalid_data` - Invalid values are rejected
7. `test_threshold_update_creates_audit_log` - Changes are logged
8. `test_thresholds_persist_across_requests` - Values persist across multiple requests

## Configuration Values

### Alert Thresholds
- **alert_berth_utilization** (0-100%): Triggers alert when berth utilization exceeds this percentage
- **alert_truck_waiting_time** (0-24 hours): Triggers alert when average truck waiting time exceeds this value

### SLA Thresholds
- **sla_turnaround** (0-168 hours): Maximum allowed time from vessel arrival to departure
- **sla_truck_waiting_time** (0-24 hours): Maximum allowed truck waiting time
- **sla_customs_dispatch** (0-168 hours): Maximum allowed customs clearance time

## Usage

### For ADMIN Users
1. Navigate to `/admin/settings/thresholds`
2. Modify any threshold values
3. Click "Guardar Cambios" to save
4. Changes are immediately persisted and cached

### For Developers
```php
// Get a threshold value
$threshold = \App\Models\Setting::getValue('alert_berth_utilization', 85);

// Set a threshold value
\App\Models\Setting::setValue('alert_berth_utilization', 90);

// Use in services
$utilization = calculateBerthUtilization();
$threshold = \App\Models\Setting::getValue('alert_berth_utilization', 85);
if ($utilization > $threshold) {
    // Generate alert
}
```

## Security Considerations

1. **RBAC**: Only ADMIN users can access settings via `permission:ADMIN` middleware
2. **Validation**: All inputs are validated with strict numeric ranges
3. **Audit Logging**: All changes are logged in `audit.audit_log` with user ID and timestamp
4. **CSRF Protection**: All forms include CSRF tokens
5. **PII**: No sensitive data is stored in settings

## Performance

- Settings are cached for 15 minutes after update
- Database queries are minimal (one query per setting retrieval if not cached)
- Suitable for high-traffic environments

## Integration Points

The settings are designed to be used by:
1. **ReportService** - For generating alerts (R11) and SLA compliance (R12)
2. **KpiCalculator** - For determining SLA compliance
3. **NotificationService** - For triggering alerts when thresholds are exceeded

## Future Enhancements

1. Add UI for configuring additional settings
2. Implement settings versioning/history
3. Add bulk import/export of settings
4. Create settings templates for different scenarios
5. Add real-time validation with Alpine.js
