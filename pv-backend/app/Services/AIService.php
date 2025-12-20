<?php

namespace App\Services;

use App\Models\Panel;
use App\Models\SensorReading;
use App\Models\AiPrediction;
use App\Jobs\ProcessAIPrediction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ai.url', 'http://localhost:8001');
        $this->timeout = config('services.ai.timeout', 5);
    }

    /**
     * Send sensor data to AI for real-time fault prediction
     */
    public function predictFault(Panel $panel, SensorReading $reading): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/predict/fault", [
                    'panel_code' => $panel->panel_code,
                    'irradiance' => $reading->irradiance,
                    'temperature' => $reading->temperature,
                    'voltage' => $reading->voltage,
                    'current' => $reading->current,
                    'power_output' => $reading->power_output,
                    'dust_level' => $reading->dust_level,
                    'humidity' => $reading->humidity,
                    'bus_voltage_pu' => $reading->bus_voltage_pu,
                    'load_demand_mw' => $reading->load_demand_mw,
                    'vocr' => $reading->vocr,
                    'power_loss_kw' => $reading->power_loss_kw,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('AI Service Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('AI Service Exception', [
                'message' => $e->getMessage(),
                'panel' => $panel->panel_code,
            ]);

            return null;
        }
    }

    /**
     * Queue prediction for async processing
     */
    public function queuePrediction(Panel $panel, SensorReading $reading): void
    {
        // Create pending prediction record
        $prediction = AiPrediction::create([
            'panel_id' => $panel->id,
            'model_type' => 'fault_detection',
            'input_data' => [
                'panel_code' => $panel->panel_code,
                'irradiance' => $reading->irradiance,
                'temperature' => $reading->temperature,
                'voltage' => $reading->voltage,
                'current' => $reading->current,
                'power_output' => $reading->power_output,
                'dust_level' => $reading->dust_level,
            ],
            'status' => 'pending',
        ]);

        // Dispatch job
        ProcessAIPrediction::dispatch($prediction);
    }

    /**
     * Request RUL prediction for a panel
     */
    public function predictRUL(Panel $panel): ?array
    {
        try {
            $readings = $panel->sensorReadings()
                ->orderByDesc('recorded_at')
                ->limit(100)
                ->get();

            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/predict/rul", [
                    'panel_code' => $panel->panel_code,
                    'installation_date' => $panel->installation_date?->toDateString(),
                    'current_efficiency' => $panel->efficiency,
                    'readings' => $readings->map(fn($r) => [
                        'temperature' => $r->temperature,
                        'voltage' => $r->voltage,
                        'power_output' => $r->power_output,
                        'recorded_at' => $r->recorded_at->toIso8601String(),
                    ])->toArray(),
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('AI RUL Prediction Error', [
                'message' => $e->getMessage(),
                'panel' => $panel->panel_code,
            ]);

            return null;
        }
    }

    /**
     * Send image for classification
     */
    public function classifyImage(Panel $panel, string $imagePath): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->attach('image', file_get_contents($imagePath), basename($imagePath))
                ->post("{$this->baseUrl}/predict/image", [
                    'panel_code' => $panel->panel_code,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('AI Image Classification Error', [
                'message' => $e->getMessage(),
                'panel' => $panel->panel_code,
            ]);

            return null;
        }
    }

    /**
     * Health check for AI service
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get AI service status and model info
     */
    public function getStatus(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/status");

            if ($response->successful()) {
                return $response->json();
            }

            return ['status' => 'unavailable'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
