<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PanelController;
use App\Http\Controllers\Api\SensorDataController;
use App\Http\Controllers\Api\FaultController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\ThresholdController;
use App\Http\Controllers\Api\AIWebhookController;
use App\Http\Controllers\Api\SystemSettingsController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ApiDocsController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Health Check
Route::get('/health', [HealthController::class, 'index']);
Route::get('/health/ping', [HealthController::class, 'ping']);
Route::get('/health/system', [HealthController::class, 'system']);

// API Documentation
Route::get('/docs', [ApiDocsController::class, 'index']);

// Authentication
Route::post('/login', [AuthController::class, 'login']);

// AI Webhooks (secured by API key in middleware)
Route::prefix('webhooks/ai')->group(function () {
    Route::post('/fault-prediction', [AIWebhookController::class, 'handleFaultPrediction']);
    Route::post('/rul-prediction', [AIWebhookController::class, 'handleRULPrediction']);
    Route::post('/image-classification', [AIWebhookController::class, 'handleImageClassification']);
    Route::post('/batch-prediction', [AIWebhookController::class, 'handleBatchPrediction']);
    Route::post('/thermal-detection', [AIWebhookController::class, 'handleThermalDetection']);
});

// Sensor data ingestion (for IoT devices)
Route::prefix('sensors')->group(function () {
    Route::post('/data', [SensorDataController::class, 'store']);
    Route::post('/batch', [SensorDataController::class, 'storeBatch']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Panels
    Route::get('/panels', [PanelController::class, 'index']);
    Route::get('/panels/grid', [PanelController::class, 'grid']);
    Route::post('/panels', [PanelController::class, 'store']);
    Route::get('/panels/{panel}', [PanelController::class, 'show']);
    Route::put('/panels/{panel}', [PanelController::class, 'update']);
    Route::delete('/panels/{panel}', [PanelController::class, 'destroy']);
    Route::get('/panels/{panel}/readings', [SensorDataController::class, 'history']);

    // Faults
    Route::get('/faults', [FaultController::class, 'index']);
    Route::post('/faults', [FaultController::class, 'store']);
    Route::get('/faults/{fault}', [FaultController::class, 'show']);
    Route::post('/faults/{fault}/resolve', [FaultController::class, 'resolve']);
    Route::patch('/faults/{fault}/status', [FaultController::class, 'updateStatus']);

    // Maintenance
    Route::get('/maintenance', [MaintenanceController::class, 'index']);
    Route::get('/maintenance/calendar', [MaintenanceController::class, 'calendar']);
    Route::post('/maintenance', [MaintenanceController::class, 'store']);
    Route::get('/maintenance/{maintenanceTask}', [MaintenanceController::class, 'show']);
    Route::put('/maintenance/{maintenanceTask}', [MaintenanceController::class, 'update']);
    Route::post('/maintenance/{maintenanceTask}/start', [MaintenanceController::class, 'start']);
    Route::post('/maintenance/{maintenanceTask}/complete', [MaintenanceController::class, 'complete']);
    Route::post('/maintenance/{maintenanceTask}/cancel', [MaintenanceController::class, 'cancel']);

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index']);
    Route::get('/analytics/panels', [AnalyticsController::class, 'panelAnalysis']);

    // Users (Admin only)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/technicians', [UserController::class, 'technicians']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    // Alerts
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::get('/alerts/unread-count', [AlertController::class, 'unreadCount']);
    Route::post('/alerts/{alert}/read', [AlertController::class, 'markAsRead']);
    Route::post('/alerts/read-all', [AlertController::class, 'markAllAsRead']);
    Route::post('/alerts/{alert}/dismiss', [AlertController::class, 'dismiss']);

    // System Thresholds
    Route::get('/thresholds', [ThresholdController::class, 'index']);
    Route::put('/thresholds', [ThresholdController::class, 'update']);
    Route::get('/thresholds/{key}', [ThresholdController::class, 'show']);

    // Weather
    Route::prefix('weather')->group(function () {
        Route::get('/current', [WeatherController::class, 'current']);
        Route::get('/coordinates', [WeatherController::class, 'byCoordinates']);
        Route::get('/forecast', [WeatherController::class, 'forecast']);
        Route::get('/search', [WeatherController::class, 'search']);
        Route::get('/solar-forecast', [WeatherController::class, 'solarForecast']);
    });

    // AI Chat
    Route::prefix('chat')->group(function () {
        Route::post('/message', [ChatController::class, 'sendMessage']);
        Route::get('/history', [ChatController::class, 'getHistory']);
        Route::delete('/history', [ChatController::class, 'clearHistory']);
        Route::get('/suggestions', [ChatController::class, 'getSuggestions']);
    });

    // System Settings (Admin only)
    Route::prefix('settings')->middleware('admin')->group(function () {
        Route::get('/', [SystemSettingsController::class, 'getAllSettings']);

        // SMTP Settings
        Route::get('/smtp', [SystemSettingsController::class, 'getSmtpSettings']);
        Route::put('/smtp', [SystemSettingsController::class, 'updateSmtpSettings']);
        Route::post('/smtp/test', [SystemSettingsController::class, 'testSmtp']);
        Route::post('/smtp/send-test', [SystemSettingsController::class, 'sendTestEmail']);

        // AI Settings
        Route::get('/ai', [SystemSettingsController::class, 'getAiSettings']);
        Route::put('/ai', [SystemSettingsController::class, 'updateAiSettings']);
        Route::post('/ai/test', [SystemSettingsController::class, 'testAi']);

        // Weather Settings
        Route::get('/weather', [SystemSettingsController::class, 'getWeatherSettings']);
        Route::put('/weather', [SystemSettingsController::class, 'updateWeatherSettings']);
        Route::post('/weather/test', [SystemSettingsController::class, 'testWeather']);

        // Email Templates
        Route::get('/email-templates', [SystemSettingsController::class, 'getEmailTemplates']);
        Route::put('/email-templates/{templateId}', [SystemSettingsController::class, 'updateEmailTemplate']);
    });
});
