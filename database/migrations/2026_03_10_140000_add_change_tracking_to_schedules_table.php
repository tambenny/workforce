<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            $table->string('change_type', 40)->default('original')->after('status');
            $table->text('change_reason')->nullable()->after('rejection_reason');
            $table->foreignId('changed_by')->nullable()->after('rejected_by')->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->dateTime('changed_at')->nullable()->after('rejected_at');
            $table->unsignedInteger('reapproval_cycle')->nullable()->after('schedule_form_id');

            $table->index(['schedule_form_id', 'change_type']);
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            $table->dropIndex(['schedule_form_id', 'change_type']);
            $table->dropConstrainedForeignId('changed_by');
            $table->dropColumn(['change_type', 'change_reason', 'changed_at', 'reapproval_cycle']);
        });
    }
};
