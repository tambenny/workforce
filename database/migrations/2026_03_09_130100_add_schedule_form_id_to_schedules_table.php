<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();
        $submittedAtExpression = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d %H:%M:%S', created_at)",
            'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s')",
            default => 'created_at',
        };

        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('schedule_form_id')->nullable()->after('id')
                ->constrained('schedule_forms')->cascadeOnUpdate()->restrictOnDelete();
        });

        $groups = DB::table('schedules')
            ->selectRaw("
                location_id,
                shift_date,
                created_by,
                {$submittedAtExpression} as submitted_at
            ")
            ->groupBy(
                'location_id',
                'shift_date',
                'created_by',
                DB::raw($submittedAtExpression)
            )
            ->get();

        foreach ($groups as $group) {
            $formId = DB::table('schedule_forms')->insertGetId([
                'location_id' => $group->location_id,
                'shift_date' => $group->shift_date,
                'created_by' => $group->created_by,
                'status' => 'submitted',
                'created_at' => $group->submitted_at,
                'updated_at' => $group->submitted_at,
            ]);

            DB::table('schedules')
                ->where('location_id', $group->location_id)
                ->whereDate('shift_date', $group->shift_date)
                ->where('created_by', $group->created_by)
                ->whereRaw("{$submittedAtExpression} = ?", [$group->submitted_at])
                ->update([
                    'schedule_form_id' => $formId,
                ]);

            $status = DB::table('schedules')
                ->where('schedule_form_id', $formId)
                ->selectRaw("
                    CASE
                        WHEN SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) > 0 THEN 'submitted'
                        WHEN SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) > 0 THEN 'rejected'
                        ELSE 'approved'
                    END as aggregate_status
                ")
                ->value('aggregate_status');

            DB::table('schedule_forms')
                ->where('id', $formId)
                ->update(['status' => $status ?: 'submitted']);
        }

        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['schedule_form_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex(['schedule_form_id', 'status']);
            $table->dropConstrainedForeignId('schedule_form_id');
        });
    }
};
