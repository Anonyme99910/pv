<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\ChatMessage;
use App\Models\Panel;
use App\Models\Fault;
use App\Models\MaintenanceTask;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatAIService
{
    protected string $apiUrl = 'https://api.deepseek.com/v1/chat/completions';
    protected ?string $apiKey = null;
    protected string $model = 'deepseek-chat';

    public function __construct()
    {
        // Try multiple setting keys for API key
        $this->apiKey = SystemSetting::get('ai_api_key')
            ?? SystemSetting::get('ai_chat_api_key')
            ?? config('services.deepseek.key');
        $this->model = SystemSetting::get('ai_model')
            ?? SystemSetting::get('ai_chat_model', 'deepseek-chat');
    }

    /**
     * Get system context for the AI
     */
    protected function getSystemContext(): string
    {
        $panelCount = Panel::count();
        $activeFaults = Fault::where('status', 'active')->count();
        $pendingMaintenance = MaintenanceTask::where('status', 'pending')->count();

        return <<<CONTEXT
You are SOMA AI Assistant, an intelligent helper for the SOMA PV Solar Panel Monitoring System.

Current System Status:
- Total Panels: {$panelCount}
- Active Faults: {$activeFaults}
- Pending Maintenance Tasks: {$pendingMaintenance}

Your capabilities:
1. Answer questions about solar panel monitoring and maintenance
2. Provide insights about system performance and faults
3. Help users understand AI predictions and recommendations
4. Assist with troubleshooting panel issues
5. Explain maintenance procedures and best practices

Guidelines:
- Be helpful, professional, and concise
- Provide actionable advice when possible
- If asked about specific panels or faults, use the context provided
- Never reveal your underlying AI model or technology
- Always refer to yourself as "SOMA AI Assistant"
CONTEXT;
    }

    /**
     * Chat with the AI
     */
    public function chat(int $userId, string $message, string $context = 'general', array $additionalContext = []): array
    {
        if (!$this->apiKey) {
            return [
                'success' => false,
                'message' => 'AI chat is not configured. Please contact administrator.',
            ];
        }

        try {
            // Build context based on request type
            $systemPrompt = $this->getSystemContext();

            if (!empty($additionalContext)) {
                $systemPrompt .= "\n\nAdditional Context:\n" . json_encode($additionalContext, JSON_PRETTY_PRINT);
            }

            // Get recent chat history for context
            $history = $this->getChatHistory($userId, 5);

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            // Add chat history
            foreach ($history as $chat) {
                $messages[] = ['role' => 'user', 'content' => $chat->message];
                if ($chat->response) {
                    $messages[] = ['role' => 'assistant', 'content' => $chat->response];
                }
            }

            // Add current message
            $messages[] = ['role' => 'user', 'content' => $message];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 500,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $aiResponse = $data['choices'][0]['message']['content'] ?? 'I apologize, but I could not generate a response.';

                // Save chat message
                ChatMessage::create([
                    'user_id' => $userId,
                    'message' => $message,
                    'response' => $aiResponse,
                    'context' => $context,
                    'metadata' => [
                        'model' => $this->model,
                        'tokens' => $data['usage'] ?? null,
                    ],
                ]);

                return [
                    'success' => true,
                    'response' => $aiResponse,
                ];
            }

            Log::error('AI Chat API error: ' . $response->body());
            return [
                'success' => false,
                'message' => 'Failed to get response from AI service.',
            ];

        } catch (\Exception $e) {
            Log::error('AI Chat exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while processing your request.',
            ];
        }
    }

    /**
     * Get chat history for a user
     */
    public function getChatHistory(int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ChatMessage::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse();
    }

    /**
     * Analyze fault with AI
     */
    public function analyzeFault(array $faultData): array
    {
        $prompt = "Analyze this solar panel fault and provide recommendations:\n" . json_encode($faultData, JSON_PRETTY_PRINT);

        return $this->chat(1, $prompt, 'fault', $faultData);
    }

    /**
     * Get maintenance suggestions
     */
    public function getMaintenanceSuggestions(array $panelData): array
    {
        $prompt = "Based on this panel data, what maintenance do you recommend?\n" . json_encode($panelData, JSON_PRETTY_PRINT);

        return $this->chat(1, $prompt, 'maintenance', $panelData);
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        if (!$this->apiKey) {
            return [
                'success' => false,
                'message' => 'API key not configured',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello'],
                ],
                'max_tokens' => 10,
            ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Connection successful' : 'Connection failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
