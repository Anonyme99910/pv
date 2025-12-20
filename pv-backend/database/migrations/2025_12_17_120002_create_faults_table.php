<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained()->onDelete('cascade');
            $table->string('fault_type', 50); // voltage_drop, temperature_anomaly, dust_accumulation, etc.
            $table->decimal('confidence', 5, 2)->default(0); // AI confidence score
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['active', 'investigating', 'resolved', 'critical'])->default('active');
            $table->json('ai_analysis')->nullable(); // Root cause, contributing factors
            $table->json('sensor_data_snapshot')->nullable(); // Sensor data at time of fault
            $table->json('suggested_actions')->nullable(); // AI suggested actions
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['panel_id', 'status']);
            $table->index(['detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faults');
    }
};
