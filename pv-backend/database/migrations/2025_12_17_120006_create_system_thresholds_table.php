<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->decimal('min_value', 10, 4)->nullable();
            $table->decimal('max_value', 10, 4)->nullable();
            $table->string('unit', 20)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_thresholds');
    }
};
