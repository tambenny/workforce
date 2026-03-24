<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Schedule;
use App\Models\ScheduleForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduleSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_view_the_weekly_schedule_summary(): void
    {
        $location = Location::create([
            'name' => 'Downtown Grill',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'location_id' => $location->id,
        ]);

        $response = $this
            ->actingAs($staff)
            ->get(route('schedules.summary'));

        $response->assertForbidden();
    }

    public function test_manager_needs_schedule_summary_permission_to_view_weekly_hours_and_details(): void
    {
        $location = Location::create([
            'name' => 'Downtown Grill',
        ]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'location_id' => $location->id,
            'can_view_schedule_summary' => false,
        ]);

        $response = $this
            ->actingAs($manager)
            ->get(route('schedules.summary'));

        $response->assertForbidden();
    }

    public function test_manager_with_schedule_summary_permission_sees_weekly_hours_and_schedule_details_for_their_location(): void
    {
        $weekStart = Carbon::parse('2026-03-16');

        $downtown = Location::create([
            'name' => 'Downtown Grill',
        ]);

        $uptown = Location::create([
            'name' => 'Uptown Grill',
        ]);

        $manager = User::factory()->create([
            'name' => 'Restaurant Manager',
            'role' => 'manager',
            'location_id' => $downtown->id,
            'can_view_schedule_summary' => true,
        ]);

        $alice = User::factory()->create([
            'name' => 'Alice Server',
            'staff_id' => 'A100',
            'role' => 'staff',
            'location_id' => $downtown->id,
        ]);

        $jamie = User::factory()->create([
            'name' => 'Jamie Cook',
            'staff_id' => 'J200',
            'role' => 'staff',
            'location_id' => $downtown->id,
        ]);

        $otherLocationStaff = User::factory()->create([
            'name' => 'Bob Other',
            'staff_id' => 'B300',
            'role' => 'staff',
            'location_id' => $uptown->id,
        ]);

        [$mondayForm] = $this->createFormWithSchedule(
            creator: $manager,
            location: $downtown,
            staff: $alice,
            shiftDate: $weekStart,
            clockIn: '10:00',
            clockOut: '16:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        $this->createFormWithSchedule(
            creator: $manager,
            location: $downtown,
            staff: $jamie,
            shiftDate: $weekStart->copy()->addDay(),
            clockIn: '09:00',
            clockOut: '17:00',
            formStatus: 'approved',
            scheduleStatus: 'approved',
        );

        $this->createFormWithSchedule(
            creator: $manager,
            location: $uptown,
            staff: $otherLocationStaff,
            shiftDate: $weekStart->copy()->addDays(2),
            clockIn: '08:00',
            clockOut: '18:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        $response = $this
            ->actingAs($manager)
            ->get(route('schedules.summary', [
                'date_from' => $weekStart->toDateString(),
                'date_to' => $weekStart->copy()->endOfWeek()->toDateString(),
            ]));

        $response
            ->assertOk()
            ->assertSee('Schedule Summary')
            ->assertSee('Downtown Grill')
            ->assertSee('Alice Server')
            ->assertSee('Jamie Cook')
            ->assertSee("Form #{$mondayForm->id}")
            ->assertSee('14.00 h')
            ->assertDontSee('Bob Other');
    }

    public function test_admin_can_filter_the_weekly_schedule_summary_by_location(): void
    {
        $weekStart = Carbon::parse('2026-03-16');

        $downtown = Location::create([
            'name' => 'Downtown Grill',
        ]);

        $uptown = Location::create([
            'name' => 'Uptown Grill',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $creator = User::factory()->create([
            'role' => 'manager',
            'location_id' => $downtown->id,
        ]);

        $alphaStaff = User::factory()->create([
            'name' => 'Alpha Staff',
            'staff_id' => 'A100',
            'role' => 'staff',
            'location_id' => $downtown->id,
        ]);

        $betaStaff = User::factory()->create([
            'name' => 'Beta Staff',
            'staff_id' => 'B200',
            'role' => 'staff',
            'location_id' => $uptown->id,
        ]);

        $this->createFormWithSchedule(
            creator: $creator,
            location: $downtown,
            staff: $alphaStaff,
            shiftDate: $weekStart,
            clockIn: '11:00',
            clockOut: '18:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        [$uptownForm] = $this->createFormWithSchedule(
            creator: $creator,
            location: $uptown,
            staff: $betaStaff,
            shiftDate: $weekStart->copy()->addDay(),
            clockIn: '12:00',
            clockOut: '17:00',
            formStatus: 'approved',
            scheduleStatus: 'approved',
        );

        $response = $this
            ->actingAs($admin)
            ->get(route('schedules.summary', [
                'date_from' => $weekStart->toDateString(),
                'date_to' => $weekStart->copy()->endOfWeek()->toDateString(),
                'location_id' => $uptown->id,
            ]));

        $response
            ->assertOk()
            ->assertSee('Beta Staff')
            ->assertSee("Form #{$uptownForm->id}")
            ->assertSee('5.00 h')
            ->assertDontSee('Alpha Staff');
    }

    public function test_hr_can_view_schedule_summary_across_all_locations_by_default(): void
    {
        $shiftDate = Carbon::parse('2026-03-23');

        $downtown = Location::create([
            'name' => 'Downtown Grill',
            'is_active' => true,
        ]);

        $uptown = Location::create([
            'name' => 'Uptown Grill',
            'is_active' => true,
        ]);

        $hr = User::factory()->create([
            'name' => 'HR Lead',
            'role' => 'hr',
            'location_id' => $downtown->id,
            'can_view_schedule_summary' => true,
        ]);

        $alice = User::factory()->create([
            'name' => 'Alice Server',
            'staff_id' => 'A100',
            'role' => 'staff',
            'location_id' => $downtown->id,
        ]);

        $bob = User::factory()->create([
            'name' => 'Bob Cook',
            'staff_id' => 'B200',
            'role' => 'staff',
            'location_id' => $uptown->id,
        ]);

        $this->createFormWithSchedule(
            creator: $hr,
            location: $downtown,
            staff: $alice,
            shiftDate: $shiftDate,
            clockIn: '09:00',
            clockOut: '17:00',
            formStatus: 'approved',
            scheduleStatus: 'approved',
        );

        $this->createFormWithSchedule(
            creator: $hr,
            location: $uptown,
            staff: $bob,
            shiftDate: $shiftDate,
            clockIn: '12:00',
            clockOut: '20:00',
            formStatus: 'approved',
            scheduleStatus: 'approved',
        );

        $response = $this
            ->actingAs($hr)
            ->get(route('schedules.summary', [
                'date_from' => $shiftDate->toDateString(),
                'date_to' => $shiftDate->toDateString(),
            ]));

        $response
            ->assertOk()
            ->assertSee('Schedule Range Overview')
            ->assertSee('All locations')
            ->assertSee('Alice Server')
            ->assertSee('Bob Cook')
            ->assertSee('16.00 h');
    }

    private function createFormWithSchedule(
        User $creator,
        Location $location,
        User $staff,
        Carbon $shiftDate,
        string $clockIn,
        string $clockOut,
        string $formStatus,
        string $scheduleStatus,
    ): array {
        $form = ScheduleForm::create([
            'location_id' => $location->id,
            'shift_date' => $shiftDate->toDateString(),
            'created_by' => $creator->id,
            'status' => $formStatus,
            'approved_by' => $formStatus === 'approved' ? $creator->id : null,
            'approved_at' => $formStatus === 'approved' ? now() : null,
        ]);

        [$startHour, $startMinute] = array_map('intval', explode(':', $clockIn));
        [$endHour, $endMinute] = array_map('intval', explode(':', $clockOut));

        $startsAt = $shiftDate->copy()->setTime($startHour, $startMinute);
        $endsAt = $shiftDate->copy()->setTime($endHour, $endMinute);

        if ($endsAt->lt($startsAt)) {
            $endsAt->addDay();
        }

        $schedule = Schedule::create([
            'schedule_form_id' => $form->id,
            'reapproval_cycle' => 1,
            'user_id' => $staff->id,
            'location_id' => $location->id,
            'shift_date' => $shiftDate->toDateString(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $scheduleStatus,
            'change_type' => 'original',
            'created_by' => $creator->id,
            'approved_by' => $scheduleStatus === 'approved' ? $creator->id : null,
            'approved_at' => $scheduleStatus === 'approved' ? now() : null,
        ]);

        return [$form, $schedule];
    }
}
