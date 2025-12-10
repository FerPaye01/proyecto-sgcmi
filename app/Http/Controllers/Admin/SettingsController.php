<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Show the thresholds settings page
     */
    public function showThresholds(): View
    {
        // Get current thresholds from database with fallback to defaults
        $thresholds = [
            'alert_berth_utilization' => Setting::getValue('alert_berth_utilization', 85),
            'alert_truck_waiting_time' => Setting::getValue('alert_truck_waiting_time', 4),
            'sla_turnaround' => Setting::getValue('sla_turnaround', 48),
            'sla_truck_waiting_time' => Setting::getValue('sla_truck_waiting_time', 2),
            'sla_customs_dispatch' => Setting::getValue('sla_customs_dispatch', 24),
        ];

        return view('admin.settings.thresholds', compact('thresholds'));
    }

    /**
     * Update thresholds settings
     */
    public function updateThresholds(Request $request): RedirectResponse
    {
        // Validate input
        $validated = $request->validate([
            'alert_berth_utilization' => 'required|numeric|min:0|max:100',
            'alert_truck_waiting_time' => 'required|numeric|min:0|max:24',
            'sla_turnaround' => 'required|numeric|min:0|max:168',
            'sla_truck_waiting_time' => 'required|numeric|min:0|max:24',
            'sla_customs_dispatch' => 'required|numeric|min:0|max:168',
        ]);

        // Store thresholds in database
        foreach ($validated as $key => $value) {
            Setting::setValue($key, $value);
        }

        // Also cache for performance (15 minutes)
        foreach ($validated as $key => $value) {
            cache(["threshold.{$key}" => $value], now()->addMinutes(15));
        }

        // Log the change in audit log
        AuditLog::create([
            'event_ts' => now(),
            'actor_user' => auth()->id(),
            'action' => 'UPDATE',
            'object_schema' => 'admin',
            'object_table' => 'settings',
            'object_id' => 0,
            'details' => json_encode($validated),
        ]);

        return redirect()->back()->with('success', 'Umbrales actualizados correctamente');
    }
}
