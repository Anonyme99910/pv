<?php

namespace Database\Seeders;

use App\Models\Panel;
use App\Models\SensorReading;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SensorDataSeeder extends Seeder
{
    public function run(): void
    {
        $panels = Panel::take(20)->get();
        $today = Carbon::today();

        foreach ($panels as $panel) {
            for ($h = 0; $h < 24; $h++) {
                // Generate realistic solar panel data
                $hour = $today->copy()->addHours($h);
                $isDaytime = $h >= 6 && $h <= 18;

                // Power output follows sun pattern
                $basePower = $isDaytime ? sin(($h - 6) * M_PI / 12) * 3.5 : 0;
                $power = max(0, $basePower + (rand(-50, 50) / 100));

                // Temperature peaks in afternoon
                $baseTemp = 25 + ($isDaytime ? sin(($h - 6) * M_PI / 12) * 20 : 0);
                $temp = $baseTemp + rand(-3, 3);

                // Voltage and current
                $voltage = $isDaytime ? rand(225, 240) : rand(0, 5);
                $current = $isDaytime ? rand(8, 14) : 0;

                // Irradiance follows sun
                $irradiance = $isDaytime ? sin(($h - 6) * M_PI / 12) * 1000 : 0;
                $irradiance = max(0, $irradiance + rand(-100, 100));

                SensorReading::create([
                    'panel_id' => $panel->id,
                    'temperature' => round($temp, 1),
                    'voltage' => $voltage,
                    'current' => $current,
                    'power_output' => round($power, 2),
                    'irradiance' => round($irradiance, 0),
                    'dust_level' => rand(0, 15),
                    'humidity' => rand(30, 70),
                    'recorded_at' => $hour,
                ]);
            }
        }

        $this->command->info('Created sensor readings for ' . $panels->count() . ' panels');
    }
}
