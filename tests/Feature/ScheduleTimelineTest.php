<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Schedule;
use App\Models\ScheduleForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduleTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_timeline_for_their_location_only(): void
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

        $manager = User::factory()->create([
            'name' => 'Restaurant Manager',
            'role' => 'manager',
            'location_id' => $downtown->id,
            'can_view_schedules' => true,
        ]);

        $alice = User::factory()->create([
            'name' => 'Alice Server',
            'staff_id' => 'A100',
            'role' => 'staff',
            'location_id' => $downtown->id,
        ]);

        $otherLocationStaff = User::factory()->create([
            'name' => 'Bob Other',
            'staff_id' => 'B200',
            'role' => 'staff',
            'location_id' => $uptown->id,
        ]);

        $this->createFormWithSchedule(
            creator: $manager,
            location: $downtown,
            staff: $alice,
            shiftDate: $shiftDate,
            clockIn: '09:00',
            clockOut: '17:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        $this->createFormWithSchedule(
            creator: $manager,
            location: $uptown,
            staff: $otherLocationStaff,
            shiftDate: $shiftDate,
            clockIn: '08:00',
            clockOut: '16:00',
            formStatus: 'approved',
            scheduleStatus: 'approved',
        );

        $response = $this
            ->actingAs($manager)
            ->get(route('schedules.timeline', [
                'shift_date' => $shiftDate->toDateString(),
            ]));

        $response
            ->assertOk()
            ->assertSee('Schedule Timeline')
            ->assertSee('Downtown Grill')
            ->assertSee('Alice Server')
            ->assertSee('9:00 AM - 5:00 PM')
            ->assertDontSee('Bob Other');
    }

    public function test_admin_can_filter_timeline_by_location(): void
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
            shiftDate: $shiftDate,
            clockIn: '10:00',
            clockOut: '18:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        $this->createFormWithSchedule(
            creator: $creator,
            location: $uptown,
            staff: $betaStaff,
            shiftDate: $shiftDate,
            clockIn: '12:00',
            clockOut: '20:00',
            formStatus: 'approved',
            scheduleStatus: 'approved',
        );

        $response = $this
            ->actingAs($admin)
            ->get(route('schedules.timeline', [
                'shift_date' => $shiftDate->toDateString(),
                'location_id' => $uptown->id,
            ]));

        $response
            ->assertOk()
            ->assertSee('Uptown Grill')
            ->assertSee('Beta Staff')
            ->assertSee('12:00 PM - 8:00 PM')
            ->assertDontSee('Alpha Staff');
    }

    public function test_hr_can_view_timeline_across_all_locations_by_default(): void
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
            'can_view_schedules' => true,
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
            ->get(route('schedules.timeline', [
                'shift_date' => $shiftDate->toDateString(),
            ]));

        $response
            ->assertOk()
            ->assertSee('All locations')
            ->assertSee('Alice Server')
            ->assertSee('Bob Cook')
            ->assertSee('9:00 AM - 5:00 PM')
            ->assertSee('12:00 PM - 8:00 PM');
    }

    public function test_timeline_caps_the_chart_at_midnight_for_overnight_shifts(): void
    {
        $shiftDate = Carbon::parse('2026-03-23');

        $location = Location::create([
            'name' => 'Late Night Kitchen',
            'is_active' => true,
        ]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'location_id' => $location->id,
            'can_view_schedules' => true,
        ]);

        $staff = User::factory()->create([
            'name' => 'Night Cook',
            'staff_id' => 'N100',
            'role' => 'staff',
            'location_id' => $location->id,
        ]);

        $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: $shiftDate,
            clockIn: '17:00',
            clockOut: '01:00',
            formStatus: 'approved',
            scheduleStatus: 'approved',
        );

        $response = $this
            ->actingAs($manager)
            ->get(route('schedules.timeline', [
                'shift_date' => $shiftDate->toDateString(),
            ]));

        $response
            ->assertOk()
            ->assertSee('5:00 PM - 12:00 AM')
            ->assertSee('5:00 PM - 12:00 AM+')
            ->assertSee('5:00 PM - Mar 24 1:00 AM');
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
