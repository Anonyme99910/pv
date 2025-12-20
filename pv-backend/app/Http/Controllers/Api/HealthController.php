<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HealthController extends Controller
{
    /**
     * Complete system health check
     */
    public function index(): JsonResponse
    {
        $checks = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'services' => [
                'laravel' => $this->checkLaravel(),
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'redis' => $this->checkRedis(),
                'ai_service' => $this->checkAIService(),
            ],
        ];

        // Determine overall status
        $unhealthy = collect($checks['services'])->filter(fn($s) => $s['status'] !== 'healthy');
        if ($unhealthy->isNotEmpty()) {
            $checks['status'] = $unhealthy->contains(fn($s) => $s['status'] === 'unhealthy') ? 'unhealthy' : 'degraded';
        }

        $statusCode = match($checks['status']) {
            'healthy' => 200,
            'degraded' => 200,
            'unhealthy' => 503,
        };

        return response()->json($checks, $statusCode);
    }

    /**
     * Quick health check (for load balancers)
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Laravel application check
     */
    protected function checkLaravel(): array
    {
        return [
            'status' => 'healthy',
            'version' => app()->version(),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
        ];
    }

    /**
     * Database connection check
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'driver' => config('database.default'),
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cache check
     */
    protected function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return [
                'status' => $value === 'test' ? 'healthy' : 'unhealthy',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'degraded',
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Redis check - Using file cache as alternative
     */
    protected function checkRedis(): array
    {
        // Check if using Redis or file cache
        $cacheDriver = config('cache.default');

        if ($cacheDriver === 'file' || $cacheDriver === 'array') {
            return [
                'status' => 'healthy',
                'driver' => $cacheDriver,
                'note' => 'Using ' . $cacheDriver . ' cache driver',
            ];
        }

        if (!extension_loaded('redis')) {
            return [
                'status' => 'healthy',
                'driver' => 'file',
                'note' => 'Fallback to file cache',
            ];
        }

        try {
            $start = microtime(true);
            $redis = new \Redis();
            $redis->connect(config('database.redis.default.host', '127.0.0.1'), config('database.redis.default.port', 6379), 1);
            $redis->ping();
            $latency = round((microtime(true) - $start) * 1000, 2);
            $redis->close();

            return [
                'status' => 'healthy',
                'driver' => 'redis',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'healthy',
                'driver' => 'file',
                'note' => 'Fallback to file cache',
            ];
        }
    }

    /**
     * AI Service check - DeepSeek API
     */
    protected function checkAIService(): array
    {
        // Check DeepSeek API configuration
        $apiKey = \App\Models\SystemSetting::get('ai_api_key');

        if (!$apiKey) {
            return [
                'status' => 'degraded',
                'error' => 'DeepSeek API key not configured',
            ];
        }

        try {
            $start = microtime(true);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(5)->post('https://api.deepseek.com/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [['role' => 'user', 'content' => 'ping']],
                'max_tokens' => 5,
            ]);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'provider' => 'DeepSeek',
                    'latency_ms' => $latency,
                ];
            }

            return [
                'status' => 'degraded',
                'provider' => 'DeepSeek',
                'error' => 'API returned error: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'degraded',
                'provider' => 'DeepSeek',
                'error' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get system information
     */
    public function system(): JsonResponse
    {
        return response()->json([
            'app' => [
                'name' => config('app.name'),
                'version' => '1.0.0',
                'environment' => config('app.env'),
                'url' => config('app.url'),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'database' => [
                'driver' => config('database.default'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
            ],
            'services' => [
                'ai_service' => config('services.ai.url', 'http://localhost:8001'),
                'cache_driver' => config('cache.default'),
                'session_driver' => config('session.driver'),
                'queue_driver' => config('queue.default'),
            ],
        ]);
    }
}
