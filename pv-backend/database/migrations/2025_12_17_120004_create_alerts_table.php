<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('fault_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['info', 'warning', 'critical'])->default('info');
            $table->string('title')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->timestamps();

            $table->index(['type', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
