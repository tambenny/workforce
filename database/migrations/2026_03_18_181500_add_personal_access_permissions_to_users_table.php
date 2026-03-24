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
            $table->boolean('can_view_dashboard')->default(true)->after('can_view_security_warnings');
            $table->boolean('can_use_web_clock')->default(true)->after('can_view_dashboard');
            $table->boolean('can_view_my_punches')->default(true)->after('can_use_web_clock');
            $table->boolean('can_view_punch_summary')->default(true)->after('can_view_my_punches');
        });

        DB::table('users')->update([
            'can_view_dashboard' => true,
            'can_use_web_clock' => true,
            'can_view_my_punches' => true,
            'can_view_punch_summary' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'can_view_dashboard',
                'can_use_web_clock',
                'can_view_my_punches',
                'can_view_punch_summary',
            ]);
        });
    }
};
