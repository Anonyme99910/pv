<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatAIService;
use App\Models\ChatMessage;
use App\Models\Panel;
use App\Models\Fault;
use App\Models\MaintenanceTask;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    protected ChatAIService $chatService;

    public function __construct(ChatAIService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Send a chat message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'context' => 'nullable|string|in:general,fault,maintenance,analytics,panel',
            'context_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $context = $request->input('context', 'general');
        $contextId = $request->input('context_id');

        // Build additional context based on request
        $additionalContext = $this->buildContext($context, $contextId);

        $result = $this->chatService->chat(
            $user->id,
            $request->message,
            $context,
            $additionalContext
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'response' => $result['response'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 500);
    }

    /**
     * Get chat history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->input('limit', 50);

        $messages = ChatMessage::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Clear chat history
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        ChatMessage::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chat history cleared',
        ]);
    }

    /**
     * Get quick suggestions based on system state
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $suggestions = [];

        // Check for active faults
        $activeFaults = Fault::where('status', 'active')->count();
        if ($activeFaults > 0) {
            $suggestions[] = [
                'text' => "Tell me about the {$activeFaults} active faults",
                'context' => 'fault',
            ];
        }

        // Check for pending maintenance
        $pendingMaintenance = MaintenanceTask::where('status', 'pending')->count();
        if ($pendingMaintenance > 0) {
            $suggestions[] = [
                'text' => "What maintenance tasks are pending?",
                'context' => 'maintenance',
            ];
        }

        // General suggestions
        $suggestions[] = [
            'text' => "How is my solar system performing today?",
            'context' => 'analytics',
        ];
        $suggestions[] = [
            'text' => "What can you help me with?",
            'context' => 'general',
        ];
        $suggestions[] = [
            'text' => "Show me efficiency recommendations",
            'context' => 'analytics',
        ];

        return response()->json([
            'success' => true,
            'suggestions' => array_slice($suggestions, 0, 5),
        ]);
    }

    /**
     * Build context for AI based on request type
     */
    protected function buildContext(string $context, ?int $contextId): array
    {
        $data = [];

        switch ($context) {
            case 'fault':
                if ($contextId) {
                    $fault = Fault::with('panel')->find($contextId);
                    if ($fault) {
                        $data['fault'] = $fault->toArray();
                    }
                } else {
                    $data['active_faults'] = Fault::where('status', 'active')
                        ->with('panel')
                        ->limit(5)
                        ->get()
                        ->toArray();
                }
                break;

            case 'maintenance':
                if ($contextId) {
                    $task = MaintenanceTask::with('panel')->find($contextId);
                    if ($task) {
                        $data['task'] = $task->toArray();
                    }
                } else {
                    $data['pending_tasks'] = MaintenanceTask::where('status', 'pending')
                        ->with('panel')
                        ->limit(5)
                        ->get()
                        ->toArray();
                }
                break;

            case 'panel':
                if ($contextId) {
                    $panel = Panel::with(['latestSensorData', 'activeFaults'])->find($contextId);
                    if ($panel) {
                        $data['panel'] = $panel->toArray();
                    }
                }
                break;

            case 'analytics':
                $data['summary'] = [
                    'total_panels' => Panel::count(),
                    'active_panels' => Panel::where('status', 'active')->count(),
                    'active_faults' => Fault::where('status', 'active')->count(),
                    'critical_faults' => Fault::where('status', 'active')->where('severity', 'critical')->count(),
                    'pending_maintenance' => MaintenanceTask::where('status', 'pending')->count(),
                ];
                break;
        }

        return $data;
    }
}
