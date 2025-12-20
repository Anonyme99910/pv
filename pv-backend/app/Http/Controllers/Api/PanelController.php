<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PanelController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'panels_' . md5(json_encode($request->all()));

        return Cache::remember($cacheKey, 60, function () use ($request) {
            $query = Panel::query();

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('zone')) {
                $query->where('zone', $request->zone);
            }

            if ($request->has('search')) {
                $query->where('panel_code', 'like', '%' . $request->search . '%');
            }

            return $query->orderBy('panel_code')->paginate($request->get('per_page', 50));
        });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'panel_code' => 'required|string|unique:panels,panel_code|max:20',
            'name' => 'nullable|string|max:255',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
            'zone' => 'nullable|integer',
            'installation_date' => 'nullable|date',
        ]);

        $panel = Panel::create($validated);

        return response()->json($panel, 201);
    }

    public function show(Panel $panel)
    {
        $panel->load(['latestReading', 'activeFaults']);

        return response()->json([
            'id' => $panel->id,
            'panel_code' => $panel->panel_code,
            'name' => $panel->name,
            'status' => $panel->status,
            'efficiency' => $panel->efficiency,
            'temperature' => $panel->temperature,
            'voltage' => $panel->voltage,
            'current' => $panel->current,
            'power_output' => $panel->power_output,
            'zone' => $panel->zone,
            'installation_date' => $panel->installation_date,
            'location' => [
                'lat' => $panel->location_lat,
                'lng' => $panel->location_lng,
            ],
            'latest_reading' => $panel->latestReading,
            'active_faults' => $panel->activeFaults,
        ]);
    }

    public function update(Request $request, Panel $panel)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location_lat' => 'sometimes|nullable|numeric',
            'location_lng' => 'sometimes|nullable|numeric',
            'zone' => 'sometimes|integer',
            'status' => 'sometimes|in:healthy,warning,fault',
            'efficiency' => 'sometimes|numeric|min:0|max:100',
        ]);

        $panel->update($validated);

        return response()->json($panel);
    }

    public function destroy(Panel $panel)
    {
        $panel->delete();

        return response()->json(['message' => 'Panel deleted successfully']);
    }

    public function grid()
    {
        $panels = Panel::select('id', 'panel_code', 'status', 'efficiency', 'temperature', 'voltage')
            ->orderBy('panel_code')
            ->get()
            ->map(function ($panel) {
                return [
                    'id' => $panel->panel_code,
                    'status' => $panel->status,
                    'efficiency' => $panel->efficiency,
                    'temperature' => $panel->temperature,
                    'voltage' => $panel->voltage,
                ];
            });

        return response()->json($panels);
    }
}
