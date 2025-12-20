<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiDocsController extends Controller
{
    /**
     * Get complete API documentation
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'api' => [
                'name' => 'SOMA PV API',
                'version' => '1.0.0',
                'description' => 'Solar Panel Monitoring System API',
                'base_url' => config('app.url') . '/api',
            ],
            'authentication' => [
                'type' => 'Bearer Token (Laravel Sanctum)',
                'header' => 'Authorization: Bearer {token}',
                'login_endpoint' => 'POST /api/login',
            ],
            'endpoints' => $this->getEndpoints(),
        ]);
    }

    /**
     * Get all API endpoints
     */
    protected function getEndpoints(): array
    {
        return [
            'health' => [
                [
                    'method' => 'GET',
                    'path' => '/api/health',
                    'description' => 'Complete system health check',
                    'auth' => false,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/health/ping',
                    'description' => 'Quick ping check',
                    'auth' => false,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/health/system',
                    'description' => 'System information',
                    'auth' => false,
                ],
            ],
            'authentication' => [
                [
                    'method' => 'POST',
                    'path' => '/api/login',
                    'description' => 'User login',
                    'auth' => false,
                    'body' => ['email' => 'string', 'password' => 'string'],
                    'response' => ['user' => 'object', 'token' => 'string'],
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/logout',
                    'description' => 'User logout',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/user',
                    'description' => 'Get current user',
                    'auth' => true,
                ],
            ],
            'dashboard' => [
                [
                    'method' => 'GET',
                    'path' => '/api/dashboard',
                    'description' => 'Get dashboard data (KPIs, charts, alerts)',
                    'auth' => true,
                ],
            ],
            'panels' => [
                [
                    'method' => 'GET',
                    'path' => '/api/panels',
                    'description' => 'List all panels',
                    'auth' => true,
                    'params' => ['status' => 'optional', 'search' => 'optional'],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/panels/grid',
                    'description' => 'Get panel grid view',
                    'auth' => true,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/panels',
                    'description' => 'Create new panel',
                    'auth' => true,
                    'body' => [
                        'panel_code' => 'string|required',
                        'location' => 'string|optional',
                        'capacity_kw' => 'number|optional',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/panels/{id}',
                    'description' => 'Get panel details',
                    'auth' => true,
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/panels/{id}',
                    'description' => 'Update panel',
                    'auth' => true,
                ],
                [
                    'method' => 'DELETE',
                    'path' => '/api/panels/{id}',
                    'description' => 'Delete panel',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/panels/{id}/readings',
                    'description' => 'Get panel sensor readings history',
                    'auth' => true,
                ],
            ],
            'sensors' => [
                [
                    'method' => 'POST',
                    'path' => '/api/sensors/data',
                    'description' => 'Submit sensor data (from ESP32)',
                    'auth' => false,
                    'body' => [
                        'panel_code' => 'string|required',
                        'irradiance' => 'number',
                        'temperature' => 'number',
                        'voltage' => 'number',
                        'current' => 'number',
                        'power_output' => 'number',
                        'humidity' => 'number',
                    ],
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/sensors/batch',
                    'description' => 'Submit batch sensor data',
                    'auth' => false,
                ],
            ],
            'faults' => [
                [
                    'method' => 'GET',
                    'path' => '/api/faults',
                    'description' => 'List all faults',
                    'auth' => true,
                    'params' => ['status' => 'optional', 'severity' => 'optional'],
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/faults',
                    'description' => 'Create fault manually',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/faults/{id}',
                    'description' => 'Get fault details',
                    'auth' => true,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/faults/{id}/resolve',
                    'description' => 'Resolve a fault',
                    'auth' => true,
                ],
                [
                    'method' => 'PATCH',
                    'path' => '/api/faults/{id}/status',
                    'description' => 'Update fault status',
                    'auth' => true,
                ],
            ],
            'maintenance' => [
                [
                    'method' => 'GET',
                    'path' => '/api/maintenance',
                    'description' => 'List maintenance tasks',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/maintenance/calendar',
                    'description' => 'Get maintenance calendar',
                    'auth' => true,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/maintenance',
                    'description' => 'Create maintenance task',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/maintenance/{id}',
                    'description' => 'Get task details',
                    'auth' => true,
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/maintenance/{id}',
                    'description' => 'Update task',
                    'auth' => true,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/maintenance/{id}/start',
                    'description' => 'Start maintenance task',
                    'auth' => true,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/maintenance/{id}/complete',
                    'description' => 'Complete maintenance task',
                    'auth' => true,
                ],
            ],
            'analytics' => [
                [
                    'method' => 'GET',
                    'path' => '/api/analytics',
                    'description' => 'Get analytics data',
                    'auth' => true,
                    'params' => ['period' => '7d|30d|90d|1y'],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/analytics/panels',
                    'description' => 'Get panel-specific analytics',
                    'auth' => true,
                ],
            ],
            'weather' => [
                [
                    'method' => 'GET',
                    'path' => '/api/weather/current',
                    'description' => 'Get current weather',
                    'auth' => true,
                    'params' => ['location' => 'string|optional (default: auto:ip)'],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/weather/forecast',
                    'description' => 'Get weather forecast',
                    'auth' => true,
                    'params' => ['location' => 'string', 'days' => 'number (1-7)'],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/weather/search',
                    'description' => 'Search locations',
                    'auth' => true,
                    'params' => ['q' => 'string'],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/weather/solar-forecast',
                    'description' => 'Get solar impact forecast',
                    'auth' => true,
                ],
            ],
            'chat' => [
                [
                    'method' => 'POST',
                    'path' => '/api/chat/message',
                    'description' => 'Send message to AI assistant',
                    'auth' => true,
                    'body' => [
                        'message' => 'string|required',
                        'context' => 'general|fault|maintenance|analytics',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/chat/history',
                    'description' => 'Get chat history',
                    'auth' => true,
                ],
                [
                    'method' => 'DELETE',
                    'path' => '/api/chat/history',
                    'description' => 'Clear chat history',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/chat/suggestions',
                    'description' => 'Get chat suggestions',
                    'auth' => true,
                ],
            ],
            'users' => [
                [
                    'method' => 'GET',
                    'path' => '/api/users',
                    'description' => 'List users (admin only)',
                    'auth' => true,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/users',
                    'description' => 'Create user (admin only)',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/users/{id}',
                    'description' => 'Get user details',
                    'auth' => true,
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/users/{id}',
                    'description' => 'Update user',
                    'auth' => true,
                ],
                [
                    'method' => 'DELETE',
                    'path' => '/api/users/{id}',
                    'description' => 'Delete user (admin only)',
                    'auth' => true,
                ],
            ],
            'alerts' => [
                [
                    'method' => 'GET',
                    'path' => '/api/alerts',
                    'description' => 'List alerts',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/alerts/unread-count',
                    'description' => 'Get unread alert count',
                    'auth' => true,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/alerts/{id}/read',
                    'description' => 'Mark alert as read',
                    'auth' => true,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/alerts/read-all',
                    'description' => 'Mark all alerts as read',
                    'auth' => true,
                ],
            ],
            'settings' => [
                [
                    'method' => 'GET',
                    'path' => '/api/settings',
                    'description' => 'Get all settings (admin only)',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/settings/smtp',
                    'description' => 'Get SMTP settings',
                    'auth' => true,
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/settings/smtp',
                    'description' => 'Update SMTP settings',
                    'auth' => true,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/settings/smtp/test',
                    'description' => 'Test SMTP connection',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/settings/ai',
                    'description' => 'Get AI settings',
                    'auth' => true,
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/settings/ai',
                    'description' => 'Update AI settings',
                    'auth' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/settings/weather',
                    'description' => 'Get weather settings',
                    'auth' => true,
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/settings/weather',
                    'description' => 'Update weather settings',
                    'auth' => true,
                ],
            ],
            'thresholds' => [
                [
                    'method' => 'GET',
                    'path' => '/api/thresholds',
                    'description' => 'Get system thresholds',
                    'auth' => true,
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/thresholds',
                    'description' => 'Update thresholds',
                    'auth' => true,
                ],
            ],
            'webhooks' => [
                [
                    'method' => 'POST',
                    'path' => '/api/webhooks/ai/fault-prediction',
                    'description' => 'Receive AI fault prediction',
                    'auth' => false,
                    'body' => [
                        'panel_code' => 'string',
                        'fault_detected' => 'boolean',
                        'fault_type' => 'string',
                        'confidence' => 'number',
                        'severity' => 'low|medium|high|critical',
                    ],
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/webhooks/ai/rul-prediction',
                    'description' => 'Receive RUL prediction',
                    'auth' => false,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/webhooks/ai/image-classification',
                    'description' => 'Receive image classification',
                    'auth' => false,
                ],
            ],
        ];
    }
}
