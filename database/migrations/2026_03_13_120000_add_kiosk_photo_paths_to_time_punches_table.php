<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_punches', function (Blueprint $table) {
            $table->string('clock_in_photo_path')->nullable()->after('clock_out_at');
            $table->string('clock_out_photo_path')->nullable()->after('clock_in_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('time_punches', function (Blueprint $table) {
            $table->dropColumn(['clock_in_photo_path', 'clock_out_photo_path']);
        });
    }
};
