# R5 KPI Implementation Summary

## Task: Calcular KPIs: pct_no_show, pct_tarde, desvio_medio_min

### Status: ✅ COMPLETED

## Overview

The three KPIs for Reporte R5 (Cumplimiento de Citas) have been successfully implemented and tested.

## KPIs Implemented

### 1. pct_no_show (Percentage of No-Show Appointments)
- **Formula**: `(NO_SHOW count / total appointments) * 100`
- **Description**: Percentage of appointments where the truck did not arrive
- **Classification**: Appointments with `estado = 'NO_SHOW'` or `hora_llegada = null`

### 2. pct_tarde (Percentage of Late Appointments)
- **Formula**: `(TARDE count / total appointments) * 100`
- **Description**: Percentage of appointments where the truck arrived more than 15 minutes late
- **Classification**: Appointments where `abs(hora_llegada - hora_programada) > 15 minutes`

### 3. desvio_medio_min (Average Deviation in Minutes)
- **Formula**: `average(hora_llegada - hora_programada)` for all appointments with arrival time
- **Description**: Average time difference between scheduled and actual arrival
- **Note**: Only includes appointments with actual arrival time (excludes NO_SHOW)

## Implementation Details

### Location
- **Service**: `app/Services/ReportService.php`
- **Method**: `calculateR5Kpis(Collection $data): array`
- **Lines**: 677-703

### Code
```php
private function calculateR5Kpis(Collection $data): array
{
    $total = $data->count();

    if ($total === 0) {
        return [
            'pct_no_show' => 0.0,
            'pct_tarde' => 0.0,
            'desvio_medio_min' => 0.0,
            'total_citas' => 0,
        ];
    }

    $noShow = $data->where('clasificacion', 'NO_SHOW')->count();
    $tarde = $data->where('clasificacion', 'TARDE')->count();
    $desvios = $data->filter(fn($cita) => $cita->desvio_min !== null)->pluck('desvio_min');

    return [
        'pct_no_show' => round(($noShow / $total) * 100, 2),
        'pct_tarde' => round(($tarde / $total) * 100, 2),
        'desvio_medio_min' => round($desvios->avg() ?? 0.0, 2),
        'total_citas' => $total,
    ];
}
```

## Classification Logic

The `generateR5` method classifies each appointment before calculating KPIs:

```php
if ($appointment->estado === 'NO_SHOW' || $appointment->hora_llegada === null) {
    $appointment->clasificacion = 'NO_SHOW';
    $appointment->desvio_min = null;
} else {
    $desvioSegundos = $appointment->hora_llegada->timestamp - $appointment->hora_programada->timestamp;
    $desvioMin = $desvioSegundos / 60;
    $appointment->desvio_min = $desvioMin;

    if (abs($desvioMin) <= 15) {
        $appointment->clasificacion = 'A_TIEMPO';
    } else {
        $appointment->clasificacion = 'TARDE';
    }
}
```

## Test Coverage

### Unit Tests
- **File**: `tests/Unit/ReportServiceTest.php`
- **Test**: `test_r5_calculates_kpis_correctly()`
- **Status**: ✅ PASSING

### Test Scenario
```
4 appointments:
- 1 A_TIEMPO (0 min deviation)
- 1 TARDE (+30 min deviation)
- 2 NO_SHOW (no arrival)

Expected Results:
- pct_no_show: 50.0% (2/4)
- pct_tarde: 25.0% (1/4)
- desvio_medio_min: 15.0 min (average of 0 and 30)
- total_citas: 4
```

### Test Results
```
✓ r5 calculates kpis correctly (10.85s)
Tests: 1 passed (4 assertions)
```

## Additional Tests

The following tests also verify R5 functionality:

1. `test_r5_classifies_appointments_correctly()` - Verifies classification logic
2. `test_r5_applies_company_scoping_for_transportista()` - Verifies scoping
3. `test_r5_hides_ranking_for_transportista()` - Verifies ranking visibility
4. `test_r5_shows_ranking_for_non_transportista()` - Verifies ranking for other roles

All tests: ✅ PASSING

## Requirements Validation

### US-3.3: Reporte R5 - Cumplimiento de Citas

✅ **Requirement**: Calcular KPIs: pct_no_show, pct_tarde, desvio_medio_min
- All three KPIs are calculated correctly
- Values are rounded to 2 decimal places
- Edge cases handled (empty data, no arrivals)

✅ **Requirement**: Clasificación: A tiempo (±15 min), Tarde, No Show
- Classification logic implemented
- Boundary conditions tested (exactly ±15 min)

✅ **Requirement**: Aplicar scoping por empresa para TRANSPORTISTA
- Scoping implemented via ScopingService
- Tests verify correct filtering

## Demo Script

A demonstration script has been created at `demo_r5_kpis.php` to show the KPI calculations in action.

## Conclusion

The task "Calcular KPIs: pct_no_show, pct_tarde, desvio_medio_min" has been successfully completed. All three KPIs are:
- ✅ Implemented correctly
- ✅ Tested thoroughly
- ✅ Following requirements
- ✅ Handling edge cases
- ✅ Integrated with the R5 report generation

The implementation follows PSR-12 standards, uses strict types, and maintains consistency with the rest of the codebase.
