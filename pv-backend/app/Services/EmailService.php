<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Configure SMTP settings from database
     */
    public function configureFromSettings(): void
    {
        $smtpSettings = SystemSetting::getGroup('smtp');

        if (empty($smtpSettings)) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $smtpSettings['smtp_host'] ?? env('MAIL_HOST'));
        Config::set('mail.mailers.smtp.port', $smtpSettings['smtp_port'] ?? env('MAIL_PORT'));
        Config::set('mail.mailers.smtp.username', $smtpSettings['smtp_username'] ?? env('MAIL_USERNAME'));
        Config::set('mail.mailers.smtp.password', $smtpSettings['smtp_password'] ?? env('MAIL_PASSWORD'));
        Config::set('mail.mailers.smtp.encryption', $smtpSettings['smtp_encryption'] ?? env('MAIL_ENCRYPTION'));
        Config::set('mail.from.address', $smtpSettings['smtp_from_address'] ?? env('MAIL_FROM_ADDRESS'));
        Config::set('mail.from.name', $smtpSettings['smtp_from_name'] ?? env('MAIL_FROM_NAME'));
    }

    /**
     * Send alert email
     */
    public function sendAlert(string $to, string $subject, string $message, array $data = []): bool
    {
        try {
            $this->configureFromSettings();

            Mail::send('emails.alert', [
                'alertMessage' => $message,
                'data' => $data,
            ], function ($mail) use ($to, $subject) {
                $mail->to($to)->subject($subject);
            });

            Log::info("Alert email sent to {$to}: {$subject}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send fault notification
     */
    public function sendFaultNotification(array $fault, array $recipients): void
    {
        $subject = "[ALERT] Fault Detected: {$fault['type']} - Panel {$fault['panel_code']}";

        $message = "A fault has been detected in your solar panel system.\n\n";
        $message .= "Panel: {$fault['panel_code']}\n";
        $message .= "Fault Type: {$fault['type']}\n";
        $message .= "Severity: {$fault['severity']}\n";
        $message .= "Confidence: {$fault['confidence']}%\n";
        $message .= "Detected At: " . now()->format('Y-m-d H:i:s') . "\n";

        foreach ($recipients as $recipient) {
            $this->sendAlert($recipient, $subject, $message, $fault);
        }
    }

    /**
     * Send maintenance reminder
     */
    public function sendMaintenanceReminder(array $task, string $to): void
    {
        $subject = "[REMINDER] Maintenance Due: {$task['title']}";

        $message = "A maintenance task is due.\n\n";
        $message .= "Task: {$task['title']}\n";
        $message .= "Panel: {$task['panel_code']}\n";
        $message .= "Scheduled: {$task['scheduled_at']}\n";
        $message .= "Priority: {$task['priority']}\n";

        $this->sendAlert($to, $subject, $message, $task);
    }

    /**
     * Send critical system alert
     */
    public function sendCriticalAlert(string $title, string $message, array $data = []): void
    {
        $recipients = $this->getAlertRecipients();
        $subject = "[CRITICAL] {$title}";

        foreach ($recipients as $recipient) {
            $this->sendAlert($recipient, $subject, $message, $data);
        }
    }

    /**
     * Get alert recipients from settings
     */
    protected function getAlertRecipients(): array
    {
        $recipients = SystemSetting::get('alert_recipients', '');
        return array_filter(array_map('trim', explode(',', $recipients)));
    }

    /**
     * Test SMTP configuration
     */
    public function testConnection(): array
    {
        try {
            $this->configureFromSettings();

            $transport = Mail::mailer('smtp')->getSymfonyTransport();

            return [
                'success' => true,
                'message' => 'SMTP connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
