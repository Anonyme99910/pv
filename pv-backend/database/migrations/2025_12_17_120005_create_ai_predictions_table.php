<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained()->onDelete('cascade');
            $table->string('model_type', 50); // fault_detection, rul_prediction, image_classification
            $table->string('model_version', 20)->nullable();
            $table->json('input_data'); // Sensor data sent to AI
            $table->json('prediction'); // AI response
            $table->decimal('confidence', 5, 2)->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['panel_id', 'model_type']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_predictions');
    }
};
