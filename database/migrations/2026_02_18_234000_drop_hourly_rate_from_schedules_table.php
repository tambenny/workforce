<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('schedules', 'hourly_rate')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->dropColumn('hourly_rate');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('schedules', 'hourly_rate')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->decimal('hourly_rate', 10, 2)->default(0)->after('ends_at');
            });
        }
    }
};
