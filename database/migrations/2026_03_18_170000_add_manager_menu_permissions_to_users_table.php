<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('can_view_schedules')->default(false)->after('can_approve_schedules');
            $table->boolean('can_view_schedule_summary')->default(false)->after('can_view_schedules');
            $table->boolean('can_view_current_staff')->default(false)->after('can_view_schedule_summary');
            $table->boolean('can_view_punch_photos')->default(false)->after('can_view_current_staff');
            $table->boolean('can_view_security_warnings')->default(false)->after('can_view_punch_photos');
        });

        DB::table('users')
            ->where('role', 'manager')
            ->update([
                'can_view_schedules' => true,
                'can_view_schedule_summary' => true,
                'can_view_current_staff' => true,
                'can_view_punch_photos' => true,
                'can_view_security_warnings' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'can_view_schedules',
                'can_view_schedule_summary',
                'can_view_current_staff',
                'can_view_punch_photos',
                'can_view_security_warnings',
            ]);
        });
    }
};
