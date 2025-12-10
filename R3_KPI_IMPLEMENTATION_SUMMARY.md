# R3 KPI Implementation Summary

## Task Completed
✅ **Calcular KPIs: utilizacion_franja, conflictos_ventana, horas_ociosas**

## Implementation Details

### 1. utilizacion_franja (Utilization per Time Slot)
**Location:** `app/Services/ReportService.php` - `calculateUtilizacionPorFranja()`

**Description:** Calculates the percentage of time each berth is occupied during configurable time slots.

**Algorithm:**
- Divides the time period into configurable slots (default: 1 hour)
- For each berth and time slot, calculates the overlap between vessel calls and the slot
- Returns utilization as a percentage (0-100%)
- Formula: `(occupied_hours / total_slot_hours) * 100`

**Example:**
- Time slot: 10:00-11:00 (1 hour)
- Vessel call: 10:00-10:30 (30 minutes)
- Utilization: 50%

### 2. conflictos_ventana (Window Conflicts)
**Location:** `app/Services/ReportService.php` - `detectarConflictos()`

**Description:** Detects overlapping vessel calls in the same berth (scheduling conflicts).

**Algorithm:**
- Groups vessel calls by berth
- Sorts calls by ATB (Actual Time of Berthing)
- Compares consecutive calls: if ATD of current > ATB of next, it's a conflict
- Counts total conflicts across all berths

**Example:**
- Call 1: 10:00-12:00
- Call 2: 11:00-13:00
- Result: 1 conflict (1-hour overlap)

**Edge Cases:**
- Consecutive calls (ATD == ATB): NOT a conflict
- Minimal overlap (1 minute): IS a conflict
- Different berths: NOT a conflict (checked per berth)

### 3. horas_ociosas (Idle Hours)
**Location:** `app/Services/ReportService.php` - `calculateR3Kpis()`

**Description:** Calculates total hours where berth utilization is below 10%.

**Algorithm:**
- Reviews all time slots across all berths
- Counts slots with utilization < 10%
- Multiplies count by slot duration (in hours)

**Example:**
- 4 time slots of 1 hour each
- Utilizations: [100%, 50%, 0%, 0%]
- Idle slots: 2 (the 0% slots)
- Idle hours: 2 hours

## Test Coverage

### Unit Tests (14 tests passing)
All tests located in `tests/Unit/ReportServiceTest.php`:

1. ✅ `test_r3_calculates_utilizacion_por_franja_correctly` - Basic utilization calculation
2. ✅ `test_r3_calculates_partial_utilization_correctly` - Partial slot occupation
3. ✅ `test_r3_calculates_utilization_with_multiple_calls_in_same_slot` - Multiple vessels
4. ✅ `test_r3_groups_utilization_by_berth` - Per-berth grouping
5. ✅ `test_r3_handles_different_slot_durations` - Configurable slot sizes
6. ✅ `test_r3_calculates_kpis_correctly` - All three KPIs together
7. ✅ `test_r3_detects_window_conflicts` - Basic conflict detection
8. ✅ `test_r3_does_not_detect_conflicts_for_consecutive_calls` - Boundary case
9. ✅ `test_r3_returns_empty_data_when_no_calls` - Empty data handling
10. ✅ `test_r3_filters_by_berth` - Berth filtering
11. ✅ `test_r3_detects_multiple_conflicts_in_same_berth` - Multiple conflicts
12. ✅ `test_r3_detects_conflicts_only_within_same_berth` - Cross-berth isolation
13. ✅ `test_r3_handles_exact_boundary_times` - Exact time boundaries
14. ✅ `test_r3_detects_minimal_overlap` - Minimal overlap detection

### Test Results
```
Tests:    80 passed (233 assertions)
Duration: 85.86s
```

## KPI Output Format

The `generateR3()` method returns:

```php
[
    'data' => Collection,  // Vessel calls with relationships
    'kpis' => [
        'utilizacion_promedio' => float,  // Average utilization across all slots
        'conflictos_ventana' => int,      // Total number of conflicts
        'horas_ociosas' => float,         // Total idle hours (< 10% utilization)
        'utilizacion_maxima' => float     // Maximum utilization observed
    ],
    'utilizacion_por_franja' => [
        'Muelle 1' => [
            '2025-01-01 10:00' => 100.0,
            '2025-01-01 11:00' => 50.0,
            // ...
        ],
        'Muelle 2' => [
            // ...
        ]
    ]
]
```

## Requirements Validation

✅ **US-2.1: Reporte R3 - Utilización de Muelles**
- Calculates hourly utilization of each berth based on ATB-ATD
- Supports configurable time slots (default: 1 hour)
- Filters by date range and berth
- Detects window conflicts (overlapping calls)
- Calculates idle hours (< 10% utilization)

## Configuration

Time slot duration can be configured via filters:
```php
$reportService->generateR3([
    'fecha_desde' => '2025-01-01 10:00:00',
    'fecha_hasta' => '2025-01-01 14:00:00',
    'franja_horas' => 2,  // 2-hour slots instead of default 1-hour
    'berth_id' => 1       // Optional: filter by specific berth
]);
```

## Performance Considerations

- Uses Eloquent eager loading for vessel and berth relationships
- Indexes on `atb`, `atd`, and `berth_id` columns improve query performance
- Time complexity: O(n*m) where n = vessel calls, m = time slots
- Suitable for typical port operations (hundreds of calls per day)

## Next Steps

The following related tasks remain:
- [ ] Create `ReportController@r3` endpoint
- [ ] Create view `reports/port/berth-utilization.blade.php`
- [ ] Implement visualization (Chart.js or Alpine.js + Tailwind)

## Compliance

✅ PSR-12 coding standards
✅ Strict types enabled
✅ PHPDoc comments
✅ No business logic in controllers
✅ Service layer pattern
✅ Comprehensive test coverage
