<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panels', function (Blueprint $table) {
            $table->id();
            $table->string('panel_code', 20)->unique(); // PV-001, PV-002
            $table->string('name')->nullable();
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();
            $table->integer('zone')->default(1);
            $table->date('installation_date')->nullable();
            $table->enum('status', ['healthy', 'warning', 'fault'])->default('healthy');
            $table->decimal('efficiency', 5, 2)->default(95.00);
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('voltage', 8, 4)->nullable();
            $table->decimal('current', 8, 4)->nullable();
            $table->decimal('power_output', 10, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panels');
    }
};
