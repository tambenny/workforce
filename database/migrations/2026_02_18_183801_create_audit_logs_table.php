<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('action', 50)->index();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
