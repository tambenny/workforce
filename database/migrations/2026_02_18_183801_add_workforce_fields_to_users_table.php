<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('staff')->after('password')->index();
            $table->foreignId('location_id')->nullable()->after('role')
                ->constrained('locations')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('location_id')->index();
            $table->string('pin_hash')->nullable()->after('is_active');
            $table->boolean('pin_enabled')->default(true)->after('pin_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn(['role', 'is_active', 'pin_hash', 'pin_enabled']);
        });
    }
};
