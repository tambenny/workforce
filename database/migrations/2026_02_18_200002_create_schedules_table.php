<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->date('shift_date')->index();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->decimal('hourly_rate', 10, 2);
            $table->string('status', 20)->default('submitted')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
