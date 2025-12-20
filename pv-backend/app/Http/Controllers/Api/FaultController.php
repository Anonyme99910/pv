<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fault;
use App\Models\Panel;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FaultController extends Controller
{
    public function index(Request $request)
    {
        $query = Fault::with('panel');

        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->whereIn('status', ['active', 'investigating']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('panel_code')) {
            $query->whereHas('panel', function ($q) use ($request) {
                $q->where('panel_code', 'like', '%' . $request->panel_code . '%');
            });
        }

        if ($request->has('fault_type')) {
            $query->where('fault_type', $request->fault_type);
        }

        $faults = $query->orderByDesc('detected_at')
            ->paginate($request->get('per_page', 20));

        $faults->getCollection()->transform(function ($fault) {
            return [
                'id' => $fault->id,
                'panelId' => $fault->panel?->panel_code ?? 'Unknown',
                'faultType' => $fault->fault_type,
                'confidence' => (float) ($fault->confidence ?? 0),
                'detectedOn' => $fault->detected_at?->toIso8601String() ?? now()->toIso8601String(),
                'status' => $fault->status ?? 'active',
                'severity' => $fault->severity ?? 'medium',
            ];
        });

        return response()->json($faults);
    }

    public function show(Fault $fault)
    {
        $fault->load(['panel', 'maintenanceTasks', 'resolvedByUser']);

        // Get sensor data for the fault period
        $sensorData = $fault->panel->sensorReadings()
            ->where('recorded_at', '>=', $fault->detected_at->subHours(48))
            ->where('recorded_at', '<=', $fault->detected_at->addHours(24))
            ->orderBy('recorded_at')
            ->get();

        $labels = $sensorData->pluck('recorded_at')->map(fn($d) => $d->format('H:i'))->toArray();

        return response()->json([
            'id' => $fault->id,
            'panelId' => $fault->panel->panel_code,
            'faultType' => $fault->fault_type,
            'confidence' => $fault->confidence,
            'detectedOn' => $fault->detected_at->toIso8601String(),
            'status' => $fault->status,
            'severity' => $fault->severity,
            'sensorData' => [
                'labels' => $labels,
                'voltage' => $sensorData->pluck('voltage')->toArray(),
                'temperature' => $sensorData->pluck('temperature')->toArray(),
                'dust' => $sensorData->pluck('dust_level')->toArray(),
            ],
            'aiAnalysis' => $fault->ai_analysis ?? [
                'rootCause' => 'Analysis pending...',
                'contributingFactors' => [],
            ],
            'suggestedActions' => $fault->suggested_actions ?? [],
            'resolvedAt' => $fault->resolved_at?->toIso8601String(),
            'resolvedBy' => $fault->resolvedByUser?->name,
            'resolutionNotes' => $fault->resolution_notes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'panel_code' => 'required|string|exists:panels,panel_code',
            'fault_type' => 'required|string|max:50',
            'confidence' => 'nullable|numeric|min:0|max:100',
            'severity' => 'nullable|in:low,medium,high,critical',
            'ai_analysis' => 'nullable|array',
            'suggested_actions' => 'nullable|array',
        ]);

        $panel = Panel::where('panel_code', $validated['panel_code'])->first();

        $fault = Fault::create([
            'panel_id' => $panel->id,
            'fault_type' => $validated['fault_type'],
            'confidence' => $validated['confidence'] ?? 0,
            'severity' => $validated['severity'] ?? 'medium',
            'status' => 'active',
            'ai_analysis' => $validated['ai_analysis'] ?? null,
            'suggested_actions' => $validated['suggested_actions'] ?? null,
            'detected_at' => now(),
        ]);

        // Update panel status
        $panel->update(['status' => $validated['severity'] === 'critical' ? 'fault' : 'warning']);

        // Create alert
        Alert::create([
            'panel_id' => $panel->id,
            'fault_id' => $fault->id,
            'type' => $validated['severity'] === 'critical' ? 'critical' : 'warning',
            'message' => "Fault detected in Panel {$panel->panel_code}: {$validated['fault_type']}",
        ]);

        return response()->json($fault, 201);
    }

    public function resolve(Request $request, Fault $fault)
    {
        $validated = $request->validate([
            'resolution_notes' => 'nullable|string',
        ]);

        $fault->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
            'resolution_notes' => $validated['resolution_notes'] ?? null,
        ]);

        // Check if panel has other active faults
        $activeFaults = $fault->panel->activeFaults()->count();
        if ($activeFaults === 0) {
            $fault->panel->update(['status' => 'healthy']);
        }

        return response()->json([
            'message' => 'Fault resolved successfully',
            'fault' => $fault,
        ]);
    }

    public function updateStatus(Request $request, Fault $fault)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,investigating,resolved,critical',
        ]);

        $fault->update(['status' => $validated['status']]);

        return response()->json($fault);
    }
}
