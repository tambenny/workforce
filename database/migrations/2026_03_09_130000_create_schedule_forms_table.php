<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schedule_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->date('shift_date')->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('submitted')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['location_id', 'shift_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_forms');
    }
};
