<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('security_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('warning_type', 50)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('message');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_warnings');
    }
};
