<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('allowed_ip', 45)->nullable()->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('position_id')->nullable()->after('location_id')
                ->constrained('positions')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('position_id');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('allowed_ip');
        });
    }
};
