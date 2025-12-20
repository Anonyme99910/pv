<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panel;
use App\Models\Fault;
use App\Models\MaintenanceTask;
use App\Models\Alert;
use App\Models\SensorReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Cache dashboard data for 30 seconds for performance
        return Cache::remember('dashboard_data', 30, function () {
            $totalPanels = Panel::count();
            $activePanels = Panel::where('status', 'healthy')->count();
            $predictedFaults = Fault::active()->count();
            $avgEfficiency = Panel::avg('efficiency') ?? 0;

            $nextMaintenance = MaintenanceTask::upcoming()
                ->orderBy('scheduled_date')
                ->first();

            return response()->json([
                'kpis' => [
                    'totalPanels' => $totalPanels,
                    'activePanels' => $activePanels,
                    'predictedFaults' => $predictedFaults,
                    'avgEfficiency' => round($avgEfficiency, 1),
                    'nextMaintenance' => $nextMaintenance?->scheduled_date,
                ],
                'powerOutput' => $this->getPowerOutput(),
                'trends' => $this->getTrends(),
                'panelGrid' => $this->getPanelGrid(),
                'alerts' => $this->getRecentAlerts(),
            ]);
        });
    }

    private function getPowerOutput()
    {
        // Optimized: Single query with grouping instead of 24 separate queries
        $hourlyData = SensorReading::whereDate('recorded_at', Carbon::today())
            ->selectRaw('HOUR(recorded_at) as hour, AVG(power_output) as avg_power')
            ->groupByRaw('HOUR(recorded_at)')
            ->pluck('avg_power', 'hour')
            ->toArray();

        $labels = [];
        $data = [];

        for ($i = 0; $i < 24; $i++) {
            $labels[] = sprintf('%02d:00', $i);
            $data[] = round($hourlyData[$i] ?? 0, 2);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getTrends()
    {
        // Optimized: Single query with grouping instead of 24 separate queries
        $hourlyData = SensorReading::whereDate('recorded_at', Carbon::today())
            ->selectRaw('HOUR(recorded_at) as hour, AVG(voltage) as avg_voltage, AVG(current) as avg_current, AVG(temperature) as avg_temp')
            ->groupByRaw('HOUR(recorded_at)')
            ->get()
            ->keyBy('hour');

        $labels = [];
        $voltage = [];
        $current = [];
        $temperature = [];

        for ($i = 0; $i < 24; $i++) {
            $labels[] = sprintf('%02d:00', $i);
            $hourData = $hourlyData->get($i);
            $voltage[] = round($hourData->avg_voltage ?? 230, 2);
            $current[] = round($hourData->avg_current ?? 10, 2);
            $temperature[] = round($hourData->avg_temp ?? 35, 2);
        }

        return [
            'labels' => $labels,
            'voltage' => $voltage,
            'current' => $current,
            'temperature' => $temperature,
        ];
    }

    private function getPanelGrid()
    {
        return Panel::select('id', 'panel_code', 'status', 'efficiency', 'temperature', 'voltage')
            ->orderBy('panel_code')
            ->get()
            ->map(function ($panel) {
                return [
                    'id' => $panel->panel_code,
                    'status' => $panel->status,
                    'efficiency' => (float) ($panel->efficiency ?? 0),
                    'temperature' => (float) ($panel->temperature ?? 0),
                    'voltage' => (float) ($panel->voltage ?? 0),
                ];
            });
    }

    private function getRecentAlerts()
    {
        return Alert::with('panel')
            ->active()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'message' => $alert->message,
                    'timestamp' => $alert->created_at->toIso8601String(),
                    'panelId' => $alert->panel?->panel_code,
                ];
            });
    }
}
