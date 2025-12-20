<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\EmailService;
use App\Services\ChatAIService;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SystemSettingsController extends Controller
{
    /**
     * Get all settings for a group
     */
    public function getGroup(string $group): JsonResponse
    {
        $settings = SystemSetting::where('group', $group)->get();

        // Mask sensitive values
        $settings = $settings->map(function ($setting) {
            if ($setting->type === 'encrypted' || str_contains($setting->key, 'password') || str_contains($setting->key, 'api_key')) {
                $setting->value = $setting->value ? '********' : null;
            }
            return $setting;
        });

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Update settings for a group
     */
    public function updateGroup(Request $request, string $group): JsonResponse
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            $type = $this->getSettingType($key);
            SystemSetting::set($key, $value, $type, $group);
        }

        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Get SMTP settings
     */
    public function getSmtpSettings(): JsonResponse
    {
        return $this->getGroup('smtp');
    }

    /**
     * Update SMTP settings
     */
    public function updateSmtpSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|in:tls,ssl,null',
            'smtp_from_address' => 'required|email',
            'smtp_from_name' => 'required|string',
            'alert_recipients' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            $type = $key === 'smtp_password' ? 'encrypted' : 'string';
            SystemSetting::set($key, $value, $type, 'smtp');
        }

        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'SMTP settings updated successfully',
        ]);
    }

    /**
     * Test SMTP connection
     */
    public function testSmtp(): JsonResponse
    {
        $emailService = new EmailService();
        $result = $emailService->testConnection();

        return response()->json($result);
    }

    /**
     * Send test email
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $emailService = new EmailService();
        $success = $emailService->sendAlert(
            $request->email,
            'SOMA PV - Test Email',
            'This is a test email from SOMA PV Solar Panel Monitoring System.',
            ['test' => true]
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Test email sent successfully' : 'Failed to send test email',
        ]);
    }

    /**
     * Get AI chat settings
     */
    public function getAiSettings(): JsonResponse
    {
        return $this->getGroup('ai');
    }

    /**
     * Update AI chat settings
     */
    public function updateAiSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ai_chat_api_key' => 'nullable|string',
            'ai_chat_model' => 'nullable|string',
            'ai_chat_enabled' => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            $type = $key === 'ai_chat_api_key' ? 'encrypted' : ($key === 'ai_chat_enabled' ? 'boolean' : 'string');
            SystemSetting::set($key, $value, $type, 'ai');
        }

        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'AI settings updated successfully',
        ]);
    }

    /**
     * Test AI connection
     */
    public function testAi(): JsonResponse
    {
        $aiService = new ChatAIService();
        $result = $aiService->testConnection();

        return response()->json($result);
    }

    /**
     * Get weather settings
     */
    public function getWeatherSettings(): JsonResponse
    {
        return $this->getGroup('weather');
    }

    /**
     * Update weather settings
     */
    public function updateWeatherSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'weather_api_key' => 'nullable|string',
            'weather_default_location' => 'nullable|string',
            'weather_enabled' => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            $type = $key === 'weather_api_key' ? 'encrypted' : ($key === 'weather_enabled' ? 'boolean' : 'string');
            SystemSetting::set($key, $value, $type, 'weather');
        }

        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Weather settings updated successfully',
        ]);
    }

    /**
     * Test weather API connection
     */
    public function testWeather(): JsonResponse
    {
        $weatherService = new WeatherService();
        $result = $weatherService->testConnection();

        return response()->json($result);
    }

    /**
     * Get all system settings (admin only)
     */
    public function getAllSettings(): JsonResponse
    {
        $groups = ['smtp', 'ai', 'weather', 'notifications', 'general'];
        $allSettings = [];

        foreach ($groups as $group) {
            $settings = SystemSetting::where('group', $group)->get();
            $allSettings[$group] = $settings->map(function ($setting) {
                if ($setting->type === 'encrypted') {
                    $setting->value = $setting->value ? '********' : null;
                }
                return $setting;
            });
        }

        return response()->json([
            'success' => true,
            'settings' => $allSettings,
        ]);
    }

    /**
     * Get email templates
     */
    public function getEmailTemplates(): JsonResponse
    {
        $templates = [
            [
                'id' => 'fault_notification',
                'name' => 'Fault Notification',
                'description' => 'Sent when a new fault is detected',
                'enabled' => SystemSetting::get('email_template_fault_enabled', true),
                'subject' => SystemSetting::get('email_template_fault_subject', 'SOMA PV Alert: Fault Detected on {{panel_code}}'),
                'body' => SystemSetting::get('email_template_fault_body', "A fault has been detected on panel {{panel_code}}.\n\nFault Type: {{fault_type}}\nSeverity: {{severity}}\nDetected At: {{detected_at}}\n\nPlease investigate immediately."),
            ],
            [
                'id' => 'maintenance_reminder',
                'name' => 'Maintenance Reminder',
                'description' => 'Sent before scheduled maintenance',
                'enabled' => SystemSetting::get('email_template_maintenance_enabled', true),
                'subject' => SystemSetting::get('email_template_maintenance_subject', 'SOMA PV: Maintenance Scheduled for {{panel_code}}'),
                'body' => SystemSetting::get('email_template_maintenance_body', "A maintenance task is scheduled.\n\nPanel: {{panel_code}}\nTask: {{task_title}}\nScheduled: {{scheduled_date}}\n\nPlease ensure the task is completed on time."),
            ],
            [
                'id' => 'critical_alert',
                'name' => 'Critical Alert',
                'description' => 'Sent for critical system alerts',
                'enabled' => SystemSetting::get('email_template_critical_enabled', true),
                'subject' => SystemSetting::get('email_template_critical_subject', 'URGENT: Critical Alert on {{panel_code}}'),
                'body' => SystemSetting::get('email_template_critical_body', "CRITICAL ALERT!\n\nPanel: {{panel_code}}\nIssue: {{message}}\nSeverity: CRITICAL\n\nImmediate action required!"),
            ],
            [
                'id' => 'daily_report',
                'name' => 'Daily Report',
                'description' => 'Daily system performance summary',
                'enabled' => SystemSetting::get('email_template_daily_enabled', false),
                'subject' => SystemSetting::get('email_template_daily_subject', 'SOMA PV Daily Report - {{date}}'),
                'body' => SystemSetting::get('email_template_daily_body', "Daily Performance Report\n\nDate: {{date}}\nTotal Energy: {{total_energy}} kWh\nAverage Efficiency: {{avg_efficiency}}%\nActive Faults: {{active_faults}}\n\nView full report in the dashboard."),
            ],
            [
                'id' => 'weekly_summary',
                'name' => 'Weekly Summary',
                'description' => 'Weekly system summary',
                'enabled' => SystemSetting::get('email_template_weekly_enabled', false),
                'subject' => SystemSetting::get('email_template_weekly_subject', 'SOMA PV Weekly Summary - Week {{week_number}}'),
                'body' => SystemSetting::get('email_template_weekly_body', "Weekly Summary Report\n\nWeek: {{week_number}}\nTotal Energy: {{total_energy}} kWh\nMaintenance Completed: {{maintenance_count}}\nFaults Resolved: {{faults_resolved}}\n\nView full report in the dashboard."),
            ],
        ];

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Update email template
     */
    public function updateEmailTemplate(Request $request, string $templateId): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:5000',
        ]);

        // Save template settings
        if (isset($validated['enabled'])) {
            SystemSetting::set("email_template_{$templateId}_enabled", $validated['enabled'], 'boolean', 'email_templates');
        }
        if (isset($validated['subject'])) {
            SystemSetting::set("email_template_{$templateId}_subject", $validated['subject'], 'string', 'email_templates');
        }
        if (isset($validated['body'])) {
            SystemSetting::set("email_template_{$templateId}_body", $validated['body'], 'string', 'email_templates');
        }

        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Email template updated successfully',
        ]);
    }

    /**
     * Determine setting type based on key
     */
    protected function getSettingType(string $key): string
    {
        if (str_contains($key, 'password') || str_contains($key, 'api_key') || str_contains($key, 'secret')) {
            return 'encrypted';
        }
        if (str_contains($key, 'enabled') || str_contains($key, 'active')) {
            return 'boolean';
        }
        if (str_contains($key, 'port') || str_contains($key, 'count') || str_contains($key, 'limit')) {
            return 'integer';
        }
        return 'string';
    }
}
