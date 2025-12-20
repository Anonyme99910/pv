<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Panel;
use App\Models\Fault;
use App\Models\Alert;
use App\Models\MaintenanceTask;
use App\Models\SensorReading;
use App\Models\SystemThreshold;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo users
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@soma.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Ahmed Ben Ali',
            'email' => 'tech@soma.com',
            'password' => Hash::make('tech123'),
            'role' => 'technician',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Fatima Zahra',
            'email' => 'fatima.zahra@soma.com',
            'password' => Hash::make('tech123'),
            'role' => 'technician',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Mohamed Amine',
            'email' => 'mohamed.amine@soma.com',
            'password' => Hash::make('tech123'),
            'role' => 'technician',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Viewer User',
            'email' => 'viewer@soma.com',
            'password' => Hash::make('viewer123'),
            'role' => 'viewer',
            'status' => 'active',
        ]);

        // Create 248 panels
        for ($i = 1; $i <= 248; $i++) {
            $status = 'healthy';
            $rand = rand(1, 100);
            if ($rand > 95) {
                $status = 'fault';
            } elseif ($rand > 85) {
                $status = 'warning';
            }

            Panel::create([
                'panel_code' => 'PV-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name' => 'Solar Panel ' . $i,
                'zone' => ceil($i / 50),
                'status' => $status,
                'efficiency' => rand(80, 98) + (rand(0, 99) / 100),
                'temperature' => rand(25, 50) + (rand(0, 99) / 100),
                'voltage' => rand(220, 240) + (rand(0, 99) / 100),
                'current' => rand(8, 12) + (rand(0, 99) / 100),
                'power_output' => rand(100, 150) + (rand(0, 99) / 100),
                'installation_date' => now()->subDays(rand(30, 1000)),
            ]);
        }

        // Create system thresholds
        SystemThreshold::create([
            'key' => 'voltage',
            'min_value' => 215,
            'max_value' => 245,
            'unit' => 'V',
            'description' => 'Acceptable voltage range',
        ]);

        SystemThreshold::create([
            'key' => 'temperature',
            'min_value' => null,
            'max_value' => 55,
            'unit' => '°C',
            'description' => 'Maximum operating temperature',
        ]);

        SystemThreshold::create([
            'key' => 'efficiency',
            'min_value' => 80,
            'max_value' => null,
            'unit' => '%',
            'description' => 'Minimum acceptable efficiency',
        ]);

        SystemThreshold::create([
            'key' => 'dust',
            'min_value' => null,
            'max_value' => 20,
            'unit' => '%',
            'description' => 'Maximum dust accumulation level',
        ]);

        // Get technicians for assignments
        $technicians = User::where('role', 'technician')->pluck('id')->toArray();

        // Create Faults for panels with issues
        $faultTypes = [
            'voltage_drop' => ['severity' => 'high', 'desc' => 'Voltage below acceptable range'],
            'temperature_anomaly' => ['severity' => 'critical', 'desc' => 'Overheating detected'],
            'dust_accumulation' => ['severity' => 'medium', 'desc' => 'Dust blocking panel surface'],
            'current_fluctuation' => ['severity' => 'medium', 'desc' => 'Irregular current patterns'],
            'efficiency_degradation' => ['severity' => 'high', 'desc' => 'Performance decline detected'],
            'connection_issue' => ['severity' => 'critical', 'desc' => 'Electrical connection problem'],
        ];

        $faultyPanels = Panel::whereIn('status', ['fault', 'warning'])->get();
        foreach ($faultyPanels as $panel) {
            $faultType = array_rand($faultTypes);
            $faultInfo = $faultTypes[$faultType];

            $fault = Fault::create([
                'panel_id' => $panel->id,
                'fault_type' => $faultType,
                'confidence' => rand(75, 98),
                'severity' => $panel->status === 'fault' ? 'critical' : $faultInfo['severity'],
                'status' => $panel->status === 'fault' ? 'critical' : 'active',
                'ai_analysis' => [
                    'root_cause' => $faultInfo['desc'],
                    'contributing_factors' => ['Environmental conditions', 'Equipment age', 'Load variations'],
                ],
                'suggested_actions' => [
                    ['action' => 'Inspect panel physically', 'priority' => 'high'],
                    ['action' => 'Check electrical connections', 'priority' => 'medium'],
                    ['action' => 'Clean panel surface', 'priority' => 'low'],
                ],
                'detected_at' => now()->subHours(rand(1, 72)),
            ]);

            // Create alert for each fault
            Alert::create([
                'panel_id' => $panel->id,
                'fault_id' => $fault->id,
                'type' => $panel->status === 'fault' ? 'critical' : 'warning',
                'title' => 'AI Fault Detection',
                'message' => "AI detected {$faultType} in Panel {$panel->panel_code} with {$fault->confidence}% confidence",
                'is_read' => rand(0, 1),
            ]);
        }

        // Create Maintenance Tasks
        $maintenanceTypes = [
            'cleaning' => ['title' => 'Panel Cleaning', 'desc' => 'Clean dust and debris from panel surface'],
            'inspection' => ['title' => 'Routine Inspection', 'desc' => 'Visual and electrical inspection'],
            'repair' => ['title' => 'Component Repair', 'desc' => 'Repair or replace faulty components'],
            'calibration' => ['title' => 'Sensor Calibration', 'desc' => 'Calibrate monitoring sensors'],
        ];

        // Past completed maintenance
        for ($i = 0; $i < 15; $i++) {
            $type = array_rand($maintenanceTypes);
            $info = $maintenanceTypes[$type];
            $panel = Panel::inRandomOrder()->first();
            $scheduledDate = now()->subDays(rand(5, 30));

            MaintenanceTask::create([
                'panel_id' => $panel->id,
                'assigned_to' => $technicians[array_rand($technicians)],
                'description' => $info['title'] . ' - ' . $panel->panel_code . ': ' . $info['desc'],
                'task_type' => $type,
                'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                'status' => 'completed',
                'scheduled_date' => $scheduledDate->format('Y-m-d'),
                'started_at' => $scheduledDate->copy()->addHours(rand(1, 4)),
                'completed_at' => $scheduledDate->copy()->addHours(rand(5, 8)),
                'completion_notes' => 'Task completed successfully. No issues found.',
            ]);
        }

        // Current in-progress maintenance
        for ($i = 0; $i < 3; $i++) {
            $type = array_rand($maintenanceTypes);
            $info = $maintenanceTypes[$type];
            $panel = Panel::inRandomOrder()->first();

            MaintenanceTask::create([
                'panel_id' => $panel->id,
                'assigned_to' => $technicians[array_rand($technicians)],
                'description' => $info['title'] . ' - ' . $panel->panel_code . ': ' . $info['desc'],
                'task_type' => $type,
                'priority' => 'high',
                'status' => 'in_progress',
                'scheduled_date' => now()->subDays(rand(0, 1))->format('Y-m-d'),
                'started_at' => now()->subHours(rand(1, 3)),
            ]);
        }

        // Upcoming scheduled maintenance
        for ($i = 0; $i < 10; $i++) {
            $type = array_rand($maintenanceTypes);
            $info = $maintenanceTypes[$type];
            $panel = Panel::inRandomOrder()->first();

            MaintenanceTask::create([
                'panel_id' => $panel->id,
                'assigned_to' => $technicians[array_rand($technicians)],
                'description' => $info['title'] . ' - ' . $panel->panel_code . ': ' . $info['desc'],
                'task_type' => $type,
                'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                'status' => 'scheduled',
                'scheduled_date' => now()->addDays(rand(1, 30))->format('Y-m-d'),
            ]);
        }

        // Create Sensor Readings for last 24 hours
        $panels = Panel::take(20)->get(); // Sample of 20 panels
        foreach ($panels as $panel) {
            for ($hour = 23; $hour >= 0; $hour--) {
                $recordedAt = now()->subHours($hour);
                $hourOfDay = (int)$recordedAt->format('H');

                // Simulate solar irradiance based on time of day
                $irradiance = 0;
                if ($hourOfDay >= 6 && $hourOfDay <= 18) {
                    $peakHour = 12;
                    $irradiance = max(0, 1000 * (1 - abs($hourOfDay - $peakHour) / 8)) + rand(-50, 50);
                }

                // Temperature varies with irradiance
                $baseTemp = 25 + ($irradiance / 50) + rand(-3, 3);

                // Power output based on irradiance and efficiency
                $efficiency = $panel->efficiency / 100;
                $powerOutput = ($irradiance * 0.3 * $efficiency) / 1000; // kW

                SensorReading::create([
                    'panel_id' => $panel->id,
                    'irradiance' => round($irradiance, 2),
                    'temperature' => round($baseTemp, 2),
                    'voltage' => round(230 + rand(-10, 10) + ($irradiance / 100), 2),
                    'current' => round(($powerOutput * 1000) / 230, 2),
                    'power_output' => round($powerOutput, 3),
                    'humidity' => rand(40, 70),
                    'dust_level' => rand(0, 15),
                    'recorded_at' => $recordedAt,
                ]);
            }
        }

        // Create some additional alerts
        $alertMessages = [
            ['type' => 'info', 'title' => 'System Update', 'message' => 'AI models updated to version 2.1'],
            ['type' => 'warning', 'title' => 'Weather Alert', 'message' => 'High temperature forecast for tomorrow'],
            ['type' => 'info', 'title' => 'Maintenance Reminder', 'message' => 'Quarterly inspection due next week'],
        ];

        foreach ($alertMessages as $alert) {
            Alert::create([
                'panel_id' => null,
                'type' => $alert['type'],
                'title' => $alert['title'],
                'message' => $alert['message'],
                'is_read' => false,
            ]);
        }

        $this->command->info('Database seeded with mock data:');
        $this->command->info('- ' . User::count() . ' users');
        $this->command->info('- ' . Panel::count() . ' panels');
        $this->command->info('- ' . Fault::count() . ' faults');
        $this->command->info('- ' . MaintenanceTask::count() . ' maintenance tasks');
        $this->command->info('- ' . SensorReading::count() . ' sensor readings');
        $this->command->info('- ' . Alert::count() . ' alerts');
    }
}
