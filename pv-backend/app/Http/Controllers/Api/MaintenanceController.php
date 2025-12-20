<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceTask;
use App\Models\Panel;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $query = MaintenanceTask::with(['panel', 'assignedUser', 'fault']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('from_date')) {
            $query->where('scheduled_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('scheduled_date', '<=', $request->to_date);
        }

        $tasks = $query->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->paginate($request->get('per_page', 30));

        $tasks->getCollection()->transform(function ($task) {
            return [
                'id' => $task->id,
                'date' => $task->scheduled_date?->toIso8601String() ?? now()->toIso8601String(),
                'equipmentId' => $task->panel?->panel_code ?? 'Unknown',
                'taskType' => $task->task_type ?? 'inspection',
                'description' => $task->description ?? '',
                'technician' => $task->assignedUser?->name ?? 'Unassigned',
                'status' => $task->status ?? 'pending',
                'priority' => $task->priority ?? 'medium',
                'duration' => (int) ($task->estimated_duration ?? 60),
            ];
        });

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'panel_code' => 'required|string|exists:panels,panel_code',
            'fault_id' => 'nullable|exists:faults,id',
            'assigned_to' => 'nullable|exists:users,id',
            'task_type' => 'required|string|in:cleaning,inspection,repair,replacement,calibration',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'estimated_duration' => 'nullable|integer|min:1',
        ]);

        $panel = Panel::where('panel_code', $validated['panel_code'])->first();

        $task = MaintenanceTask::create([
            'panel_id' => $panel->id,
            'fault_id' => $validated['fault_id'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'created_by' => $request->user()->id,
            'task_type' => $validated['task_type'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'estimated_duration' => $validated['estimated_duration'] ?? 60,
            'status' => 'scheduled',
        ]);

        return response()->json([
            'success' => true,
            'id' => $task->id,
            'task' => $task->load(['panel', 'assignedUser']),
        ], 201);
    }

    public function show(MaintenanceTask $maintenanceTask)
    {
        $maintenanceTask->load(['panel', 'assignedUser', 'createdByUser', 'fault']);

        return response()->json($maintenanceTask);
    }

    public function update(Request $request, MaintenanceTask $maintenanceTask)
    {
        $validated = $request->validate([
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'task_type' => 'sometimes|string|in:cleaning,inspection,repair,replacement,calibration',
            'description' => 'sometimes|nullable|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'scheduled_date' => 'sometimes|date',
            'scheduled_time' => 'sometimes|nullable|date_format:H:i',
            'estimated_duration' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
        ]);

        $maintenanceTask->update($validated);

        return response()->json($maintenanceTask->load(['panel', 'assignedUser']));
    }

    public function start(MaintenanceTask $maintenanceTask)
    {
        $maintenanceTask->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Maintenance task started',
            'task' => $maintenanceTask,
        ]);
    }

    public function complete(Request $request, MaintenanceTask $maintenanceTask)
    {
        $validated = $request->validate([
            'completion_notes' => 'nullable|string',
            'actual_duration' => 'nullable|integer|min:1',
        ]);

        $maintenanceTask->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_notes' => $validated['completion_notes'] ?? null,
            'actual_duration' => $validated['actual_duration'] ??
                ($maintenanceTask->started_at ?
                    now()->diffInMinutes($maintenanceTask->started_at) :
                    $maintenanceTask->estimated_duration),
        ]);

        return response()->json([
            'message' => 'Maintenance task completed',
            'task' => $maintenanceTask,
        ]);
    }

    public function cancel(MaintenanceTask $maintenanceTask)
    {
        $maintenanceTask->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Maintenance task cancelled',
            'task' => $maintenanceTask,
        ]);
    }

    public function calendar(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $tasks = MaintenanceTask::with(['panel', 'assignedUser'])
            ->whereBetween('scheduled_date', [$startDate, $endDate])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get()
            ->groupBy(fn($task) => $task->scheduled_date->format('Y-m-d'));

        return response()->json($tasks);
    }
}
