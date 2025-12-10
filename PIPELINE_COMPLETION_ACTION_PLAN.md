# SGCMI - Pipeline Completion Action Plan

**Status**: Ready for Execution  
**Target Completion**: 100% (from current 79%)  
**Estimated Timeline**: 2-3 weeks  
**Priority**: Complete test suite and route implementation

---

## Phase 1: Test Suite Completion (Priority 1)

### Task 1.1: Fix Test Database Configuration
**Objective**: Isolate test database from production  
**Effort**: 30 minutes  
**Status**: Ready

**Steps**:
1. Update `phpunit.xml` to use `sgcmi_test` database
2. Configure `RefreshDatabase` trait in `tests/TestCase.php`
3. Verify test database is created and migrations run
4. Run existing tests to confirm fixes

**Files to Modify**:
- `phpunit.xml` - Add DB_DATABASE=sgcmi_test
- `tests/TestCase.php` - Add RefreshDatabase trait

**Expected Result**: All 13 tests run without database conflicts

---

### Task 1.2: Add Missing Tests (12 tests needed)

**Objective**: Reach minimum 25 tests with 50% coverage  
**Effort**: 4 hours  
**Status**: Ready

#### Test Group 1: ReportService (3 tests)
```php
// tests/Feature/ReportServiceTest.php
- test_generate_r1_calculates_kpis_correctly()
- test_generate_r4_applies_company_scoping()
- test_generate_r7_shows_customs_status()
```

#### Test Group 2: KpiCalculator (3 tests)
```php
// tests/Unit/KpiCalculatorTest.php (expand existing)
- test_calculate_turnaround_returns_hours()
- test_calculate_waiting_time_returns_hours()
- test_calculate_appointment_compliance_returns_percentage()
```

#### Test Group 3: ExportService (3 tests)
```php
// tests/Feature/ExportServiceTest.php
- test_export_csv_generates_valid_file()
- test_export_xlsx_generates_valid_file()
- test_export_pdf_generates_valid_file()
```

#### Test Group 4: AuditService (3 tests)
```php
// tests/Unit/AuditServiceTest.php (expand existing)
- test_audit_log_created_on_create()
- test_audit_log_created_on_update()
- test_audit_log_sanitizes_pii()
```

**Expected Result**: 25 tests total, ~50% code coverage

---

## Phase 2: Route Implementation (Priority 2)

### Task 2.1: Implement Web Routes
**Objective**: Connect controllers to HTTP endpoints  
**Effort**: 2 hours  
**Status**: Ready

**Routes to Add** (in `routes/web.php`):

```php
// Authentication
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// Portuario Module
Route::prefix('portuario')->middleware(['auth', 'permission:SCHEDULE_READ'])->group(function () {
    Route::get('/vessel-calls', [VesselCallController::class, 'index']);
    Route::get('/vessel-calls/create', [VesselCallController::class, 'create']);
    Route::post('/vessel-calls', [VesselCallController::class, 'store'])->middleware('permission:SCHEDULE_WRITE');
    Route::get('/vessel-calls/{id}/edit', [VesselCallController::class, 'edit']);
    Route::patch('/vessel-calls/{id}', [VesselCallController::class, 'update'])->middleware('permission:SCHEDULE_WRITE');
    Route::delete('/vessel-calls/{id}', [VesselCallController::class, 'destroy'])->middleware('permission:SCHEDULE_WRITE');
});

// Terrestre Module
Route::prefix('terrestre')->middleware('auth')->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'index'])->middleware('permission:APPOINTMENT_READ');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->middleware('permission:APPOINTMENT_WRITE');
    Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('permission:APPOINTMENT_WRITE');
    Route::get('/appointments/{id}/edit', [AppointmentController::class, 'edit'])->middleware('permission:APPOINTMENT_WRITE');
    Route::patch('/appointments/{id}', [AppointmentController::class, 'update'])->middleware('permission:APPOINTMENT_WRITE');
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy'])->middleware('permission:APPOINTMENT_WRITE');
    
    Route::get('/gate-events', [GateEventController::class, 'index'])->middleware('permission:GATE_EVENT_READ');
    Route::post('/gate-events', [GateEventController::class, 'store'])->middleware('permission:GATE_EVENT_WRITE');
});

// Aduanas Module
Route::prefix('aduanas')->middleware('auth')->group(function () {
    Route::get('/tramites', [TramiteController::class, 'index'])->middleware('permission:ADUANA_READ');
    Route::get('/tramites/create', [TramiteController::class, 'create'])->middleware('permission:ADUANA_WRITE');
    Route::post('/tramites', [TramiteController::class, 'store'])->middleware('permission:ADUANA_WRITE');
    Route::get('/tramites/{id}', [TramiteController::class, 'show'])->middleware('permission:ADUANA_READ');
    Route::post('/tramites/{id}/eventos', [TramiteController::class, 'addEvent'])->middleware('permission:ADUANA_WRITE');
});

// Reports Module
Route::prefix('reports')->middleware(['auth', 'permission:REPORT_READ'])->group(function () {
    Route::get('/port/schedule-vs-actual', [ReportController::class, 'r1'])->middleware('permission:PORT_REPORT_READ');
    Route::get('/port/berth-utilization', [ReportController::class, 'r3'])->middleware('permission:PORT_REPORT_READ');
    Route::get('/road/waiting-time', [ReportController::class, 'r4'])->middleware('permission:ROAD_REPORT_READ');
    Route::get('/road/appointments-compliance', [ReportController::class, 'r5'])->middleware('permission:ROAD_REPORT_READ');
    Route::get('/road/gate-productivity', [ReportController::class, 'r6'])->middleware('permission:ROAD_REPORT_READ');
    Route::get('/cus/status-by-vessel', [ReportController::class, 'r7'])->middleware('permission:CUS_REPORT_READ');
    Route::get('/cus/dispatch-time', [ReportController::class, 'r8'])->middleware('permission:CUS_REPORT_READ');
    Route::get('/cus/doc-incidents', [ReportController::class, 'r9'])->middleware('permission:CUS_REPORT_READ');
    Route::get('/kpi/panel', [ReportController::class, 'r10'])->middleware('permission:KPI_READ');
    Route::get('/analytics/early-warning', [ReportController::class, 'r11'])->middleware('permission:KPI_READ');
    Route::get('/sla/compliance', [ReportController::class, 'r12'])->middleware('permission:SLA_READ');
});

// Export Module
Route::post('/export/{report}', [ExportController::class, 'export'])->middleware(['auth', 'permission:REPORT_EXPORT']);

// Admin Module
Route::prefix('admin')->middleware(['auth', 'permission:ADMIN'])->group(function () {
    Route::get('/settings/thresholds', [SettingsController::class, 'showThresholds']);
    Route::patch('/settings/thresholds', [SettingsController::class, 'updateThresholds']);
});
```

**Expected Result**: All 30+ routes registered and accessible

---

### Task 2.2: Create AuthController
**Objective**: Handle login/logout  
**Effort**: 1 hour  
**Status**: Ready

**File**: `app/Http/Controllers/AuthController.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        return back()->withErrors(['username' => 'Invalid credentials']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
```

---

## Phase 3: Service Layer Testing (Priority 3)

### Task 3.1: Test ReportService Methods
**Objective**: Verify all 12 report generation methods  
**Effort**: 3 hours  
**Status**: Ready

**Test Coverage**:
- R1: Schedule vs Actual (Portuario)
- R3: Berth Utilization (Portuario)
- R4: Waiting Time (Terrestre) with scoping
- R5: Appointments Compliance (Terrestre) with scoping
- R6: Gate Productivity (Terrestre)
- R7: Customs Status (Aduanas)
- R8: Dispatch Time (Aduanas)
- R9: Doc Incidents (Aduanas)
- R10: KPI Panel (Analytics)
- R11: Early Warning (Analytics)
- R12: SLA Compliance (Analytics)

**Expected Result**: All report methods tested and verified

---

### Task 3.2: Test KpiCalculator Methods
**Objective**: Verify KPI calculation accuracy  
**Effort**: 2 hours  
**Status**: Ready

**Test Coverage**:
- Turnaround time calculation
- Waiting time calculation
- Appointment compliance classification
- Customs lead time calculation
- Percentile calculations (p50, p90)

**Expected Result**: All KPI calculations verified

---

## Phase 4: Frontend Integration (Priority 4)

### Task 4.1: Connect Forms to Controllers
**Objective**: Ensure form submissions work correctly  
**Effort**: 2 hours  
**Status**: Ready

**Forms to Test**:
- Vessel Call create/edit
- Appointment create/edit
- Tramite create/edit
- Gate Event create

**Expected Result**: All forms submit and save data correctly

---

### Task 4.2: Implement Alpine.js Components
**Objective**: Add interactivity to forms  
**Effort**: 2 hours  
**Status**: Ready

**Components**:
- Date validation (ETB >= ETA, etc.)
- Report filters with URL persistence
- KPI panel auto-refresh
- Modal dialogs
- Confirmation dialogs

**Expected Result**: All components functional and tested

---

## Phase 5: Performance Optimization (Priority 5)

### Task 5.1: Query Optimization
**Objective**: Ensure queries use indexes and eager loading  
**Effort**: 2 hours  
**Status**: Ready

**Optimizations**:
- Add eager loading with `with()`
- Implement pagination (50 records/page)
- Add query caching for KPIs
- Verify index usage

**Expected Result**: All queries optimized and fast

---

### Task 5.2: Frontend Performance
**Objective**: Optimize asset loading and rendering  
**Effort**: 1 hour  
**Status**: Ready

**Optimizations**:
- Minify CSS and JS
- Lazy load images
- Implement pagination in tables
- Cache static assets

**Expected Result**: Page load time < 2 seconds

---

## Phase 6: Documentation (Priority 6)

### Task 6.1: API Documentation
**Objective**: Document all endpoints  
**Effort**: 2 hours  
**Status**: Ready

**Format**: Postman collection or OpenAPI spec

**Coverage**:
- All CRUD endpoints
- All report endpoints
- All export endpoints
- Authentication endpoints

---

### Task 6.2: User Guides
**Objective**: Create role-specific user guides  
**Effort**: 3 hours  
**Status**: Ready

**Guides**:
- PLANIFICADOR_PUERTO guide
- OPERADOR_GATES guide
- TRANSPORTISTA guide
- AGENTE_ADUANA guide
- ANALISTA guide
- DIRECTIVO guide

---

## Execution Timeline

### Week 1
- **Day 1-2**: Phase 1 (Test suite completion)
- **Day 3-4**: Phase 2 (Route implementation)
- **Day 5**: Phase 3 (Service layer testing)

### Week 2
- **Day 1-2**: Phase 4 (Frontend integration)
- **Day 3-4**: Phase 5 (Performance optimization)
- **Day 5**: Phase 6 (Documentation)

### Week 3
- **Day 1-2**: Integration testing
- **Day 3-4**: User acceptance testing
- **Day 5**: Deployment preparation

---

## Success Criteria

### Code Quality
- [x] PSR-12 compliance: 100%
- [x] PHPStan level 5: PASS
- [ ] Test coverage: 50% (target)
- [ ] Tests passing: 25/25 (target)

### Functionality
- [ ] All 12 reports working
- [ ] All CRUD operations working
- [ ] All exports working (CSV, XLSX, PDF)
- [ ] All permissions enforced

### Performance
- [ ] Page load time < 2 seconds
- [ ] Report generation < 5 seconds
- [ ] Export generation < 10 seconds

### Security
- [ ] All PII masked in exports
- [ ] All audit logs recorded
- [ ] All permissions enforced
- [ ] No sensitive data in logs

---

## Risk Mitigation

### Risk 1: Test Database Conflicts
**Mitigation**: Use separate test database (sgcmi_test)  
**Contingency**: Use database transactions for tests

### Risk 2: Performance Issues
**Mitigation**: Add indexes and eager loading  
**Contingency**: Implement query caching

### Risk 3: Frontend Integration Issues
**Mitigation**: Test each form individually  
**Contingency**: Use browser DevTools for debugging

### Risk 4: Security Vulnerabilities
**Mitigation**: Follow OWASP guidelines  
**Contingency**: Conduct security audit

---

## Rollback Plan

If any phase fails:
1. Revert to last stable commit
2. Identify root cause
3. Fix issue in separate branch
4. Re-test before merging
5. Document lessons learned

---

## Sign-Off

**Prepared By**: SGCMI Development Team  
**Date**: December 3, 2025  
**Status**: Ready for Execution  
**Next Step**: Execute Phase 1 (Test Suite Completion)

