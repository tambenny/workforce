<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_create_schedules')->default(false)->after('role');
            $table->boolean('can_approve_schedules')->default(false)->after('can_create_schedules');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_create_schedules', 'can_approve_schedules']);
        });
    }
};
