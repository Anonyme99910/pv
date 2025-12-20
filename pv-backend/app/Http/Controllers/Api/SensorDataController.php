<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panel;
use App\Models\SensorReading;
use App\Services\AIService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SensorDataController extends Controller
{
    public function __construct(
        protected AIService $aiService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'panel_code' => 'required|string|exists:panels,panel_code',
            'irradiance' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'voltage' => 'nullable|numeric',
            'current' => 'nullable|numeric',
            'power_output' => 'nullable|numeric',
            'dust_level' => 'nullable|numeric',
            'humidity' => 'nullable|numeric',
            'season' => 'nullable|string',
            'inverter_id' => 'nullable|string',
            'bus_voltage_pu' => 'nullable|numeric',
            'load_demand_mw' => 'nullable|numeric',
            'control_scheme' => 'nullable|string',
            'reactive_power_mvar' => 'nullable|numeric',
            'voltage_setting_pu' => 'nullable|numeric',
            'vocr' => 'nullable|numeric',
            'power_loss_kw' => 'nullable|numeric',
            'recorded_at' => 'nullable|date',
        ]);

        $panel = Panel::where('panel_code', $validated['panel_code'])->first();

        unset($validated['panel_code']);
        $validated['panel_id'] = $panel->id;
        $validated['recorded_at'] = $validated['recorded_at'] ?? now();

        $reading = SensorReading::create($validated);

        // Update panel's current readings
        $panel->update([
            'temperature' => $validated['temperature'] ?? $panel->temperature,
            'voltage' => $validated['voltage'] ?? $panel->voltage,
            'current' => $validated['current'] ?? $panel->current,
            'power_output' => $validated['power_output'] ?? $panel->power_output,
        ]);

        // Trigger AI prediction asynchronously (only if AI service is available)
        try {
            $this->aiService->queuePrediction($panel, $reading);
        } catch (\Exception $e) {
            // AI service unavailable - continue without prediction
        }

        return response()->json([
            'message' => 'Sensor data recorded successfully',
            'reading' => $reading,
            'ai_prediction_queued' => true,
        ], 201);
    }

    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'readings' => 'required|array',
            'readings.*.panel_code' => 'required|string|exists:panels,panel_code',
            'readings.*.irradiance' => 'nullable|numeric',
            'readings.*.temperature' => 'nullable|numeric',
            'readings.*.voltage' => 'nullable|numeric',
            'readings.*.current' => 'nullable|numeric',
            'readings.*.power_output' => 'nullable|numeric',
            'readings.*.dust_level' => 'nullable|numeric',
            'readings.*.recorded_at' => 'nullable|date',
        ]);

        $created = [];

        foreach ($validated['readings'] as $data) {
            $panel = Panel::where('panel_code', $data['panel_code'])->first();

            unset($data['panel_code']);
            $data['panel_id'] = $panel->id;
            $data['recorded_at'] = $data['recorded_at'] ?? now();

            $reading = SensorReading::create($data);
            $created[] = $reading;

            // Update panel
            $panel->update([
                'temperature' => $data['temperature'] ?? $panel->temperature,
                'voltage' => $data['voltage'] ?? $panel->voltage,
                'current' => $data['current'] ?? $panel->current,
                'power_output' => $data['power_output'] ?? $panel->power_output,
            ]);
        }

        return response()->json([
            'message' => 'Batch sensor data recorded successfully',
            'count' => count($created),
        ], 201);
    }

    public function history(Request $request, Panel $panel)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'limit' => 'nullable|integer|max:1000',
        ]);

        $query = $panel->sensorReadings()
            ->orderByDesc('recorded_at');

        if ($request->has('from')) {
            $query->where('recorded_at', '>=', Carbon::parse($request->from));
        }

        if ($request->has('to')) {
            $query->where('recorded_at', '<=', Carbon::parse($request->to));
        }

        $readings = $query->limit($request->get('limit', 100))->get();

        return response()->json($readings);
    }
}
