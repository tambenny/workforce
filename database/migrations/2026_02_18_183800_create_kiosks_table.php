<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kiosks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('location_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('kiosk_token_hash')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosks');
    }
};
