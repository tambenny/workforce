<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('time_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('kiosk_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('source', 20)->default('kiosk');
            $table->dateTime('clock_in_at');
            $table->dateTime('clock_out_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'clock_out_at']);
            $table->index('kiosk_id');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_punches');
    }
};
