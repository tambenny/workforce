<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('time_punches', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable()->after('kiosk_id')
                ->constrained('schedules')->cascadeOnUpdate()->nullOnDelete();
            $table->text('violation_note')->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('time_punches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schedule_id');
            $table->dropColumn('violation_note');
        });
    }
};
