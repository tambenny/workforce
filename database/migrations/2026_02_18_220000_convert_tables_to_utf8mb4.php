<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'users',
            'locations',
            'kiosks',
            'time_punches',
            'audit_logs',
            'positions',
            'schedules',
            'security_warnings',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
    }

    public function down(): void
    {
        // Keep utf8mb4 to avoid data loss/regression for multilingual content.
    }
};
