<?php

namespace App\Jobs;

use App\Models\AiPrediction;
use App\Models\Fault;
use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessAIPrediction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        protected AiPrediction $prediction
    ) {}

    public function handle(): void
    {
        $aiUrl = config('services.ai.url', 'http://localhost:8000');
        $startTime = microtime(true);

        try {
            $response = Http::timeout(30)
                ->post("{$aiUrl}/predict/fault", $this->prediction->input_data);

            $processingTime = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $result = $response->json();

                $this->prediction->update([
                    'prediction' => $result,
                    'confidence' => $result['confidence'] ?? null,
                    'processing_time_ms' => $processingTime,
                    'status' => 'completed',
                ]);

                // If fault detected, create fault record
                if ($result['fault_detected'] ?? false) {
                    $this->createFault($result);
                }
            } else {
                $this->prediction->update([
                    'status' => 'failed',
                    'error_message' => "HTTP {$response->status()}: {$response->body()}",
                ]);
            }
        } catch (\Exception $e) {
            Log::error('AI Prediction Job Failed', [
                'prediction_id' => $this->prediction->id,
                'error' => $e->getMessage(),
            ]);

            $this->prediction->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function createFault(array $result): void
    {
        $panel = $this->prediction->panel;

        $fault = Fault::create([
            'panel_id' => $panel->id,
            'fault_type' => $result['fault_type'] ?? 'unknown',
            'confidence' => $result['confidence'] ?? 0,
            'severity' => $result['severity'] ?? 'medium',
            'status' => ($result['severity'] ?? 'medium') === 'critical' ? 'critical' : 'active',
            'ai_analysis' => $result['ai_analysis'] ?? null,
            'suggested_actions' => $result['suggested_actions'] ?? null,
            'sensor_data_snapshot' => $this->prediction->input_data,
            'detected_at' => now(),
        ]);

        // Update panel status
        $panel->update([
            'status' => ($result['severity'] ?? 'medium') === 'critical' ? 'fault' : 'warning',
        ]);

        // Create alert
        Alert::create([
            'panel_id' => $panel->id,
            'fault_id' => $fault->id,
            'type' => ($result['severity'] ?? 'medium') === 'critical' ? 'critical' : 'warning',
            'title' => 'AI Fault Detection',
            'message' => "AI detected {$result['fault_type']} in Panel {$panel->panel_code}",
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->prediction->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
