<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->name('dashboard');

// API para reportes (requiere autenticación)
Route::middleware('auth')->group(function () {
    Route::get('/api/report/{code}', [\App\Http\Controllers\DashboardController::class, 'getReport'])
        ->name('api.report');
});

// Test frontend route (development only)
Route::get('/test-frontend', function () {
    return view('test-frontend');
});

// Authentication routes are handled by auth.php

// Admin Module Routes
Route::prefix('admin')->middleware(['auth', 'permission:ADMIN'])->group(function () {
    Route::prefix('settings')->group(function () {
        Route::get('/thresholds', [\App\Http\Controllers\Admin\SettingsController::class, 'showThresholds'])
            ->name('admin.settings.thresholds.show');
        
        Route::patch('/thresholds', [\App\Http\Controllers\Admin\SettingsController::class, 'updateThresholds'])
            ->name('admin.settings.thresholds.update');
    });
});

// Portuario Module Routes
Route::prefix('portuario')->middleware(['auth'])->group(function () {
    Route::resource('vessel-calls', \App\Http\Controllers\VesselCallController::class)
        ->names([
            'index' => 'vessel-calls.index',
            'create' => 'vessel-calls.create',
            'store' => 'vessel-calls.store',
            'show' => 'vessel-calls.show',
            'edit' => 'vessel-calls.edit',
            'update' => 'vessel-calls.update',
            'destroy' => 'vessel-calls.destroy',
        ]);
    
    // Vessel Planning Routes
    Route::prefix('vessel-planning')->group(function () {
        Route::get('/service-request', [\App\Http\Controllers\Portuario\VesselPlanningController::class, 'createServiceRequest'])
            ->name('vessel-planning.service-request');
        
        Route::post('/service-request', [\App\Http\Controllers\Portuario\VesselPlanningController::class, 'storeServiceRequest'])
            ->name('vessel-planning.store-service-request');
        
        Route::get('/{vesselCall}', [\App\Http\Controllers\Portuario\VesselPlanningController::class, 'show'])
            ->name('vessel-planning.show');
        
        Route::get('/{vesselCall}/validate-arrival', function (\App\Models\VesselCall $vesselCall) {
            $vesselCall->load(['vessel', 'berth', 'shipParticulars']);
            return view('portuario.vessel-planning.validate-arrival', compact('vesselCall'));
        })->name('vessel-planning.validate-arrival');
        
        Route::post('/{vesselCall}/validate-arrival', [\App\Http\Controllers\Portuario\VesselPlanningController::class, 'validateArrival'])
            ->name('vessel-planning.validate-arrival.post');
    });
    
    // Resource Planning Routes
    Route::prefix('resource-planning')->group(function () {
        Route::get('/', [\App\Http\Controllers\Portuario\ResourcePlanningController::class, 'index'])
            ->name('resource-planning.index');
        
        Route::post('/allocate', [\App\Http\Controllers\Portuario\ResourcePlanningController::class, 'allocateResources'])
            ->name('resource-planning.allocate');
        
        Route::patch('/{allocation}', [\App\Http\Controllers\Portuario\ResourcePlanningController::class, 'updateAllocation'])
            ->name('resource-planning.update');
    });
    
    // Operations Meeting Routes
    Route::prefix('operations-meeting')->group(function () {
        Route::get('/', [\App\Http\Controllers\Portuario\OperationsMeetingController::class, 'index'])
            ->name('operations-meeting.index');
        
        Route::get('/create', [\App\Http\Controllers\Portuario\OperationsMeetingController::class, 'create'])
            ->name('operations-meeting.create');
        
        Route::post('/', [\App\Http\Controllers\Portuario\OperationsMeetingController::class, 'store'])
            ->name('operations-meeting.store');
        
        Route::get('/{operationsMeeting}', [\App\Http\Controllers\Portuario\OperationsMeetingController::class, 'show'])
            ->name('operations-meeting.show');
        
        Route::get('/{operationsMeeting}/edit', [\App\Http\Controllers\Portuario\OperationsMeetingController::class, 'edit'])
            ->name('operations-meeting.edit');
        
        Route::patch('/{operationsMeeting}', [\App\Http\Controllers\Portuario\OperationsMeetingController::class, 'update'])
            ->name('operations-meeting.update');
        
        Route::delete('/{operationsMeeting}', [\App\Http\Controllers\Portuario\OperationsMeetingController::class, 'destroy'])
            ->name('operations-meeting.destroy');
    });
});

// Terrestre Module Routes
Route::prefix('terrestre')->middleware(['auth'])->group(function () {
    // Appointments
    Route::get('/appointments', [\App\Http\Controllers\AppointmentController::class, 'index'])
        ->middleware('permission:APPOINTMENT_READ')
        ->name('appointments.index');
    
    Route::get('/appointments/create', [\App\Http\Controllers\AppointmentController::class, 'create'])
        ->middleware('permission:APPOINTMENT_WRITE')
        ->name('appointments.create');
    
    Route::post('/appointments', [\App\Http\Controllers\AppointmentController::class, 'store'])
        ->middleware('permission:APPOINTMENT_WRITE')
        ->name('appointments.store');
    
    Route::patch('/appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'update'])
        ->middleware('permission:APPOINTMENT_WRITE')
        ->name('appointments.update');
    
    Route::delete('/appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'destroy'])
        ->middleware('permission:APPOINTMENT_WRITE')
        ->name('appointments.destroy');
    
    // Gate Events
    Route::get('/gate-events', [\App\Http\Controllers\GateEventController::class, 'index'])
        ->middleware('permission:GATE_EVENT_READ')
        ->name('gate-events.index');
    
    Route::post('/gate-events', [\App\Http\Controllers\GateEventController::class, 'store'])
        ->middleware('permission:GATE_EVENT_WRITE')
        ->name('gate-events.store');
});

// Aduanas Module Routes
Route::prefix('aduanas')->middleware(['auth'])->group(function () {
    // Tramites
    Route::get('/tramites', [\App\Http\Controllers\TramiteController::class, 'index'])
        ->middleware('permission:ADUANA_READ')
        ->name('tramites.index');
    
    Route::get('/tramites/create', [\App\Http\Controllers\TramiteController::class, 'create'])
        ->middleware('permission:ADUANA_WRITE')
        ->name('tramites.create');
    
    Route::post('/tramites', [\App\Http\Controllers\TramiteController::class, 'store'])
        ->middleware('permission:ADUANA_WRITE')
        ->name('tramites.store');
    
    Route::get('/tramites/{tramite}', [\App\Http\Controllers\TramiteController::class, 'show'])
        ->middleware('permission:ADUANA_READ')
        ->name('tramites.show');
    
    Route::get('/tramites/{tramite}/edit', [\App\Http\Controllers\TramiteController::class, 'edit'])
        ->middleware('permission:ADUANA_WRITE')
        ->name('tramites.edit');
    
    Route::patch('/tramites/{tramite}', [\App\Http\Controllers\TramiteController::class, 'update'])
        ->middleware('permission:ADUANA_WRITE')
        ->name('tramites.update');
    
    Route::delete('/tramites/{tramite}', [\App\Http\Controllers\TramiteController::class, 'destroy'])
        ->middleware('permission:ADUANA_WRITE')
        ->name('tramites.destroy');
    
    // Tramite Events
    Route::post('/tramites/{tramite}/eventos', [\App\Http\Controllers\TramiteController::class, 'addEvent'])
        ->middleware('permission:ADUANA_WRITE')
        ->name('tramites.addEvent');
});

// Reports Module Routes
Route::prefix('reports')->middleware(['auth'])->group(function () {
    // Port Reports
    Route::prefix('port')->group(function () {
        Route::get('/schedule-vs-actual', [\App\Http\Controllers\ReportController::class, 'r1'])
            ->middleware('permission:PORT_REPORT_READ')
            ->name('reports.r1');
        
        Route::get('/berth-utilization', [\App\Http\Controllers\ReportController::class, 'r3'])
            ->middleware('permission:PORT_REPORT_READ')
            ->name('reports.r3');
    });
    
    // Road Reports
    Route::prefix('road')->group(function () {
        Route::get('/waiting-time', [\App\Http\Controllers\ReportController::class, 'r4'])
            ->middleware('permission:ROAD_REPORT_READ')
            ->name('reports.r4');
        
        Route::get('/appointments-compliance', [\App\Http\Controllers\ReportController::class, 'r5'])
            ->middleware('permission:ROAD_REPORT_READ')
            ->name('reports.r5');
        
        Route::get('/gate-productivity', [\App\Http\Controllers\ReportController::class, 'r6'])
            ->middleware('permission:ROAD_REPORT_READ')
            ->name('reports.r6');
    });
    
    // Customs Reports
    Route::prefix('cus')->group(function () {
        Route::get('/status-by-vessel', [\App\Http\Controllers\ReportController::class, 'r7'])
            ->middleware('permission:CUS_REPORT_READ')
            ->name('reports.r7');
        
        Route::get('/dispatch-time', [\App\Http\Controllers\ReportController::class, 'r8'])
            ->middleware('permission:CUS_REPORT_READ')
            ->name('reports.r8');
        
        Route::get('/doc-incidents', [\App\Http\Controllers\ReportController::class, 'r9'])
            ->middleware('permission:CUS_REPORT_READ')
            ->name('reports.r9');
    });
    
    // KPI Reports
    Route::prefix('kpi')->group(function () {
        Route::get('/panel', [\App\Http\Controllers\ReportController::class, 'r10'])
            ->middleware('permission:KPI_READ')
            ->name('reports.r10');
        
        // API endpoint for polling KPI data (used by Alpine.js)
        Route::get('/panel/api', [\App\Http\Controllers\ReportController::class, 'r10Api'])
            ->middleware('permission:KPI_READ')
            ->name('reports.r10.api');
    });
    
    // Analytics Reports
    Route::prefix('analytics')->group(function () {
        Route::get('/early-warning', [\App\Http\Controllers\ReportController::class, 'r11'])
            ->middleware('permission:KPI_READ')
            ->name('reports.r11');
        
        // API endpoint for polling early warning alerts (used by Alpine.js)
        Route::get('/early-warning/api', [\App\Http\Controllers\ReportController::class, 'r11Api'])
            ->middleware('permission:KPI_READ')
            ->name('reports.r11.api');
    });
    
    // SLA Reports
    Route::prefix('sla')->group(function () {
        Route::get('/compliance', [\App\Http\Controllers\ReportController::class, 'r12'])
            ->middleware('permission:SLA_READ')
            ->name('reports.r12');
    });
});

// Export Routes with Rate Limiting (5/minute per steering rules)
Route::prefix('export')->middleware(['auth', 'throttle:exports'])->group(function () {
    // Generic export route that handles all report types
    Route::post('/{report}', [\App\Http\Controllers\ExportController::class, 'export'])
        ->middleware('permission:REPORT_EXPORT')
        ->name('export.report')
        ->where('report', 'r[0-9]+');
    
    // Legacy route for backward compatibility
    Route::post('/r1', [\App\Http\Controllers\ExportController::class, 'exportR1'])
        ->middleware('permission:REPORT_EXPORT')
        ->name('export.r1');
});
