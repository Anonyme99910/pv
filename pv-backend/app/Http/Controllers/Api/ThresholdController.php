<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemThreshold;
use Illuminate\Http\Request;

class ThresholdController extends Controller
{
    public function index()
    {
        $thresholds = SystemThreshold::all();

        $formatted = [];
        foreach ($thresholds as $threshold) {
            $formatted[$threshold->key] = [
                'min' => $threshold->min_value,
                'max' => $threshold->max_value,
                'unit' => $threshold->unit,
            ];
        }

        return response()->json($formatted);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'voltage.min' => 'nullable|numeric',
            'voltage.max' => 'nullable|numeric',
            'temperature.max' => 'nullable|numeric',
            'efficiency.min' => 'nullable|numeric',
            'dust.max' => 'nullable|numeric',
        ]);

        foreach ($validated as $key => $values) {
            SystemThreshold::updateOrCreate(
                ['key' => $key],
                [
                    'min_value' => $values['min'] ?? null,
                    'max_value' => $values['max'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Thresholds updated successfully',
        ]);
    }

    public function show(string $key)
    {
        $threshold = SystemThreshold::where('key', $key)->first();

        if (!$threshold) {
            return response()->json(['message' => 'Threshold not found'], 404);
        }

        return response()->json([
            'key' => $threshold->key,
            'min' => $threshold->min_value,
            'max' => $threshold->max_value,
            'unit' => $threshold->unit,
            'description' => $threshold->description,
        ]);
    }
}
