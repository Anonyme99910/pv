<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panel;
use App\Models\Fault;
use App\Models\Alert;
use App\Models\AiPrediction;
use App\Models\User;
use App\Services\EmailService;
use App\Events\FaultDetected;
use App\Events\AlertCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIWebhookController extends Controller
{
    /**
     * Receive fault prediction from Python AI service
     */
    public function handleFaultPrediction(Request $request)
    {
        $validated = $request->validate([
            'panel_code' => 'required|string|exists:panels,panel_code',
            'fault_detected' => 'required|boolean',
            'fault_type' => 'nullable|string',
            'confidence' => 'required|numeric|min:0|max:100',
            'severity' => 'nullable|in:low,medium,high,critical',
            'ai_analysis' => 'nullable|array',
            'ai_analysis.root_cause' => 'nullable|string',
            'ai_analysis.contributing_factors' => 'nullable|array',
            'suggested_actions' => 'nullable|array',
            'model_version' => 'nullable|string',
            'processing_time_ms' => 'nullable|integer',
            'prediction_id' => 'nullable|integer',
        ]);

        $panel = Panel::where('panel_code', $validated['panel_code'])->first();

        // Update AI prediction record if exists
        if (isset($validated['prediction_id'])) {
            AiPrediction::where('id', $validated['prediction_id'])->update([
                'prediction' => $validated,
                'confidence' => $validated['confidence'],
                'processing_time_ms' => $validated['processing_time_ms'] ?? null,
                'status' => 'completed',
            ]);
        }

        // If fault detected, create fault record
        if ($validated['fault_detected']) {
            $fault = Fault::create([
                'panel_id' => $panel->id,
                'fault_type' => $validated['fault_type'] ?? 'unknown',
                'confidence' => $validated['confidence'],
                'severity' => $validated['severity'] ?? 'medium',
                'status' => $validated['severity'] === 'critical' ? 'critical' : 'active',
                'ai_analysis' => $validated['ai_analysis'] ?? null,
                'suggested_actions' => $validated['suggested_actions'] ?? null,
                'detected_at' => now(),
            ]);

            // Update panel status
            $panel->update([
                'status' => $validated['severity'] === 'critical' ? 'fault' : 'warning',
            ]);

            // Create alert
            Alert::create([
                'panel_id' => $panel->id,
                'fault_id' => $fault->id,
                'type' => $validated['severity'] === 'critical' ? 'critical' : 'warning',
                'title' => 'AI Fault Detection',
                'message' => "AI detected {$validated['fault_type']} in Panel {$panel->panel_code} with {$validated['confidence']}% confidence",
            ]);

            Log::info('AI Fault Detected', [
                'panel' => $panel->panel_code,
                'fault_type' => $validated['fault_type'],
                'confidence' => $validated['confidence'],
            ]);

            // Broadcast real-time event
            event(new FaultDetected($fault->toArray()));
            event(new AlertCreated($alert->toArray()));

            // Send email notification for critical/high severity faults
            if (in_array($validated['severity'], ['critical', 'high'])) {
                $this->sendFaultEmail($panel, $fault, $validated);
            }

            return response()->json([
                'status' => 'fault_created',
                'fault_id' => $fault->id,
            ]);
        }

        return response()->json(['status' => 'processed', 'fault_detected' => false]);
    }

    /**
     * Receive RUL (Remaining Useful Life) prediction from Python AI service
     */
    public function handleRULPrediction(Request $request)
    {
        $validated = $request->validate([
            'panel_code' => 'required|string|exists:panels,panel_code',
            'rul_days' => 'required|integer|min:0',
            'confidence' => 'required|numeric|min:0|max:100',
            'degradation_rate' => 'nullable|numeric',
            'model_version' => 'nullable|string',
        ]);

        $panel = Panel::where('panel_code', $validated['panel_code'])->first();

        // Store prediction
        AiPrediction::create([
            'panel_id' => $panel->id,
            'model_type' => 'rul_prediction',
            'model_version' => $validated['model_version'] ?? '1.0',
            'input_data' => ['panel_code' => $validated['panel_code']],
            'prediction' => [
                'rul_days' => $validated['rul_days'],
                'degradation_rate' => $validated['degradation_rate'] ?? null,
            ],
            'confidence' => $validated['confidence'],
            'status' => 'completed',
        ]);

        // Create alert if RUL is critical (< 180 days)
        if ($validated['rul_days'] < 180) {
            Alert::create([
                'panel_id' => $panel->id,
                'type' => 'warning',
                'title' => 'Low RUL Warning',
                'message' => "Panel {$panel->panel_code} has only {$validated['rul_days']} days of remaining useful life",
            ]);
        }

        return response()->json(['status' => 'processed']);
    }

    /**
     * Receive image classification result from Python AI service
     */
    public function handleImageClassification(Request $request)
    {
        $validated = $request->validate([
            'panel_code' => 'required|string|exists:panels,panel_code',
            'classification' => 'required|string', // clean, dusty, bird_drop, physical_damage, etc.
            'confidence' => 'required|numeric|min:0|max:100',
            'image_path' => 'nullable|string',
            'model_version' => 'nullable|string',
        ]);

        $panel = Panel::where('panel_code', $validated['panel_code'])->first();

        // Store prediction
        AiPrediction::create([
            'panel_id' => $panel->id,
            'model_type' => 'image_classification',
            'model_version' => $validated['model_version'] ?? '1.0',
            'input_data' => ['image_path' => $validated['image_path'] ?? null],
            'prediction' => [
                'classification' => $validated['classification'],
            ],
            'confidence' => $validated['confidence'],
            'status' => 'completed',
        ]);

        // If not clean, create fault
        if ($validated['classification'] !== 'clean' && $validated['confidence'] > 70) {
            $faultType = match ($validated['classification']) {
                'dusty' => 'dust_accumulation',
                'bird_drop' => 'contamination',
                'physical_damage' => 'physical_damage',
                'electrical_damage' => 'electrical_damage',
                'snow_covered' => 'snow_coverage',
                default => 'visual_anomaly',
            };

            $fault = Fault::create([
                'panel_id' => $panel->id,
                'fault_type' => $faultType,
                'confidence' => $validated['confidence'],
                'severity' => $validated['confidence'] > 90 ? 'high' : 'medium',
                'status' => 'active',
                'ai_analysis' => [
                    'root_cause' => "Visual inspection detected: {$validated['classification']}",
                    'contributing_factors' => [],
                ],
                'detected_at' => now(),
            ]);

            Alert::create([
                'panel_id' => $panel->id,
                'fault_id' => $fault->id,
                'type' => 'warning',
                'title' => 'Visual Anomaly Detected',
                'message' => "AI image analysis detected {$validated['classification']} on Panel {$panel->panel_code}",
            ]);

            return response()->json([
                'status' => 'fault_created',
                'fault_id' => $fault->id,
            ]);
        }

        return response()->json(['status' => 'processed', 'classification' => $validated['classification']]);
    }

    /**
     * Receive thermal detection results from Python AI service (YOLOv9)
     */
    public function handleThermalDetection(Request $request)
    {
        $validated = $request->validate([
            'detection_type' => 'required|string|in:thermal',
            'panel_code' => 'nullable|string|exists:panels,panel_code',
            'fault_detected' => 'required|boolean',
            'anomaly_count' => 'nullable|integer|min:0',
            'overall_severity' => 'nullable|in:low,medium,high,critical',
            'detections' => 'nullable|array',
            'detections.*.class_id' => 'nullable|integer',
            'detections.*.fault_type' => 'nullable|string',
            'detections.*.severity' => 'nullable|string',
            'detections.*.description' => 'nullable|string',
            'detections.*.confidence' => 'nullable|numeric',
            'detections.*.bbox_normalized' => 'nullable|array',
            'suggested_actions' => 'nullable|array',
            'ai_analysis' => 'nullable|array',
            'image_base64' => 'nullable|string',
        ]);

        // If no panel code, log and return
        if (empty($validated['panel_code'])) {
            Log::info('Thermal detection received without panel code', $validated);
            return response()->json(['status' => 'logged', 'message' => 'No panel code provided']);
        }

        $panel = Panel::where('panel_code', $validated['panel_code'])->first();

        // Store AI prediction
        $prediction = AiPrediction::create([
            'panel_id' => $panel->id,
            'model_type' => 'thermal_detection',
            'model_version' => 'YOLOv9-Th_G_v9',
            'input_data' => [
                'detection_type' => 'thermal',
                'has_image' => !empty($validated['image_base64']),
            ],
            'prediction' => [
                'anomaly_count' => $validated['anomaly_count'] ?? 0,
                'detections' => $validated['detections'] ?? [],
                'ai_analysis' => $validated['ai_analysis'] ?? [],
            ],
            'confidence' => $validated['detections'][0]['confidence'] ?? 0,
            'status' => 'completed',
        ]);

        // Save annotated image if provided
        if (!empty($validated['image_base64'])) {
            $imagePath = 'thermal_detections/' . $panel->panel_code . '_' . now()->format('Ymd_His') . '.jpg';
            $imageData = base64_decode($validated['image_base64']);
            \Storage::disk('public')->put($imagePath, $imageData);
            $prediction->update(['input_data->image_path' => $imagePath]);
        }

        // If faults detected, create fault records
        if ($validated['fault_detected'] && !empty($validated['detections'])) {
            $faultTypes = [];
            $maxSeverity = 'low';
            $severityOrder = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];

            foreach ($validated['detections'] as $detection) {
                $faultTypes[] = $detection['fault_type'] ?? 'thermal_anomaly';
                $detSeverity = $detection['severity'] ?? 'medium';
                if (($severityOrder[$detSeverity] ?? 0) > ($severityOrder[$maxSeverity] ?? 0)) {
                    $maxSeverity = $detSeverity;
                }
            }

            // Create single fault record with all detections
            $fault = Fault::create([
                'panel_id' => $panel->id,
                'fault_type' => 'thermal_' . ($faultTypes[0] ?? 'anomaly'),
                'confidence' => $validated['detections'][0]['confidence'] ?? 0,
                'severity' => $maxSeverity,
                'status' => $maxSeverity === 'critical' ? 'critical' : 'active',
                'ai_analysis' => [
                    'detection_method' => 'YOLOv9 Thermal Detection',
                    'anomaly_count' => $validated['anomaly_count'] ?? count($validated['detections']),
                    'fault_types_detected' => array_unique($faultTypes),
                    'detections' => $validated['detections'],
                    'summary' => $validated['ai_analysis']['summary'] ?? null,
                    'risk_level' => $validated['ai_analysis']['risk_level'] ?? $maxSeverity,
                ],
                'suggested_actions' => $validated['suggested_actions'] ?? [],
                'detected_at' => now(),
            ]);

            // Update panel status
            $panel->update([
                'status' => $maxSeverity === 'critical' ? 'fault' : 'warning',
            ]);

            // Create alert
            $alert = Alert::create([
                'panel_id' => $panel->id,
                'fault_id' => $fault->id,
                'type' => $maxSeverity === 'critical' ? 'critical' : 'warning',
                'title' => 'Thermal Anomaly Detected',
                'message' => "Thermal imaging detected {$validated['anomaly_count']} anomalies on Panel {$panel->panel_code}: " . implode(', ', array_unique($faultTypes)),
            ]);

            Log::info('Thermal fault detected', [
                'panel' => $panel->panel_code,
                'anomaly_count' => $validated['anomaly_count'],
                'severity' => $maxSeverity,
                'fault_types' => $faultTypes,
            ]);

            // Broadcast events
            event(new FaultDetected($fault->toArray()));
            event(new AlertCreated($alert->toArray()));

            // Send email for critical/high severity
            if (in_array($maxSeverity, ['critical', 'high'])) {
                $this->sendFaultEmail($panel, $fault, [
                    'fault_type' => 'Thermal: ' . implode(', ', array_unique($faultTypes)),
                    'severity' => $maxSeverity,
                    'confidence' => $validated['detections'][0]['confidence'] ?? 0,
                ]);
            }

            return response()->json([
                'status' => 'fault_created',
                'fault_id' => $fault->id,
                'prediction_id' => $prediction->id,
                'anomaly_count' => $validated['anomaly_count'],
            ]);
        }

        return response()->json([
            'status' => 'processed',
            'fault_detected' => false,
            'prediction_id' => $prediction->id,
        ]);
    }

    /**
     * Batch prediction results from Python AI service
     */
    public function handleBatchPrediction(Request $request)
    {
        $validated = $request->validate([
            'predictions' => 'required|array',
            'predictions.*.panel_code' => 'required|string|exists:panels,panel_code',
            'predictions.*.fault_detected' => 'required|boolean',
            'predictions.*.fault_type' => 'nullable|string',
            'predictions.*.confidence' => 'required|numeric',
            'predictions.*.severity' => 'nullable|string',
        ]);

        $results = [];

        foreach ($validated['predictions'] as $prediction) {
            if ($prediction['fault_detected']) {
                $panel = Panel::where('panel_code', $prediction['panel_code'])->first();

                $fault = Fault::create([
                    'panel_id' => $panel->id,
                    'fault_type' => $prediction['fault_type'] ?? 'unknown',
                    'confidence' => $prediction['confidence'],
                    'severity' => $prediction['severity'] ?? 'medium',
                    'status' => 'active',
                    'detected_at' => now(),
                ]);

                $panel->update(['status' => 'warning']);

                $results[] = [
                    'panel_code' => $prediction['panel_code'],
                    'fault_id' => $fault->id,
                ];
            }
        }

        return response()->json([
            'status' => 'processed',
            'faults_created' => count($results),
            'results' => $results,
        ]);
    }

    /**
     * Send fault notification email via SMTP
     */
    protected function sendFaultEmail(Panel $panel, Fault $fault, array $data): void
    {
        try {
            $emailService = new EmailService();

            // Get admin users to notify
            $admins = User::where('role', 'admin')->pluck('email')->toArray();

            if (empty($admins)) {
                Log::warning('No admin users found for fault email notification');
                return;
            }

            $faultData = [
                'panel_code' => $panel->panel_code,
                'type' => $data['fault_type'] ?? 'Unknown',
                'severity' => $data['severity'] ?? 'medium',
                'confidence' => $data['confidence'] ?? 0,
            ];

            $emailService->sendFaultNotification($faultData, $admins);

            Log::info('Fault email notification sent', [
                'panel' => $panel->panel_code,
                'recipients' => $admins,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send fault email: ' . $e->getMessage());
        }
    }
}
