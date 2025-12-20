<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained()->onDelete('cascade');
            $table->decimal('irradiance', 8, 2)->nullable(); // W/m²
            $table->decimal('temperature', 5, 2)->nullable(); // °C
            $table->decimal('voltage', 8, 4)->nullable(); // V
            $table->decimal('current', 8, 4)->nullable(); // A
            $table->decimal('power_output', 10, 4)->nullable(); // kW
            $table->decimal('dust_level', 5, 2)->nullable(); // %
            $table->decimal('humidity', 5, 2)->nullable(); // %
            $table->string('season', 20)->nullable();
            $table->string('inverter_id', 20)->nullable();
            $table->decimal('bus_voltage_pu', 8, 4)->nullable();
            $table->decimal('load_demand_mw', 8, 4)->nullable();
            $table->string('control_scheme', 50)->nullable();
            $table->decimal('reactive_power_mvar', 8, 4)->nullable();
            $table->decimal('voltage_setting_pu', 8, 4)->nullable();
            $table->decimal('vocr', 8, 4)->nullable();
            $table->decimal('power_loss_kw', 8, 4)->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['panel_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
