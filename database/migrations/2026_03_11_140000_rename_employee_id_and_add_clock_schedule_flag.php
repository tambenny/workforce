<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->renameColumn('employee_id', 'staff_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('requires_schedule_for_clock')->default(true)->after('pin_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('requires_schedule_for_clock');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->renameColumn('staff_id', 'employee_id');
        });
    }
};
