<?php

namespace Tests\Feature;

use App\Models\Kiosk;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\ScheduleForm;
use App\Models\TimePunch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleSplitShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_a_non_overlapping_second_shift_for_the_same_staff_and_day(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: $shiftDate,
            clockIn: '09:00',
            clockOut: '12:00',
            formStatus: 'approved',
            scheduleStatus: 'approved',
        );

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.create'))
            ->post(route('schedules.store'), [
                'location_id' => $location->id,
                'shift_date' => $shiftDate->toDateString(),
                'roster' => [
                    $staff->id => [
                        'selected' => '1',
                        'clock_in' => '13:00',
                        'clock_out' => '17:00',
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('schedules.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Schedule::query()
            ->where('user_id', $staff->id)
            ->whereDate('shift_date', $shiftDate->toDateString())
            ->count());
    }

    public function test_manager_can_create_multiple_non_overlapping_lines_for_the_same_staff_in_one_submission(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.create'))
            ->post(route('schedules.store'), [
                'location_id' => $location->id,
                'shift_date' => $shiftDate->toDateString(),
                'roster' => [
                    $staff->id => [
                        'lines' => [
                            [
                                'selected' => '1',
                                'clock_in' => '09:00',
                                'clock_out' => '12:00',
                            ],
                            [
                                'selected' => '1',
                                'clock_in' => '13:00',
                                'clock_out' => '17:00',
                            ],
                        ],
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('schedules.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Schedule::query()
            ->where('user_id', $staff->id)
            ->whereDate('shift_date', $shiftDate->toDateString())
            ->count());
    }

    public function test_manager_can_create_an_overnight_shift_when_clock_out_is_on_the_next_day(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.create'))
            ->post(route('schedules.store'), [
                'location_id' => $location->id,
                'shift_date' => $shiftDate->toDateString(),
                'roster' => [
                    $staff->id => [
                        'selected' => '1',
                        'clock_in' => '18:00',
                        'clock_out' => '02:00',
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('schedules.index'))
            ->assertSessionHasNoErrors();

        $schedule = Schedule::query()->where('user_id', $staff->id)->firstOrFail();

        $this->assertSame('2026-03-20', $schedule->shift_date->toDateString());
        $this->assertSame('2026-03-20 18:00', $schedule->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-03-21 02:00', $schedule->ends_at->format('Y-m-d H:i'));
    }

    public function test_manager_cannot_create_an_overlapping_second_shift_for_the_same_staff_and_day(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: $shiftDate,
            clockIn: '09:00',
            clockOut: '12:00',
            formStatus: 'approved',
            scheduleStatus: 'approved',
        );

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.create'))
            ->post(route('schedules.store'), [
                'location_id' => $location->id,
                'shift_date' => $shiftDate->toDateString(),
                'roster' => [
                    $staff->id => [
                        'selected' => '1',
                        'clock_in' => '11:00',
                        'clock_out' => '14:00',
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('schedules.create'))
            ->assertSessionHasErrors('roster');

        $this->assertSame(1, Schedule::query()
            ->where('user_id', $staff->id)
            ->whereDate('shift_date', $shiftDate->toDateString())
            ->count());
    }

    public function test_manager_cannot_create_overlapping_lines_for_the_same_staff_in_one_submission(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.create'))
            ->post(route('schedules.store'), [
                'location_id' => $location->id,
                'shift_date' => $shiftDate->toDateString(),
                'roster' => [
                    $staff->id => [
                        'lines' => [
                            [
                                'selected' => '1',
                                'clock_in' => '09:00',
                                'clock_out' => '12:00',
                            ],
                            [
                                'selected' => '1',
                                'clock_in' => '11:00',
                                'clock_out' => '14:00',
                            ],
                        ],
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('schedules.create'))
            ->assertSessionHasErrors('roster');

        $this->assertSame(0, Schedule::query()
            ->where('user_id', $staff->id)
            ->whereDate('shift_date', $shiftDate->toDateString())
            ->count());
    }

    public function test_manager_cannot_create_a_next_day_shift_that_overlaps_an_existing_overnight_shift(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');

        $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: Carbon::parse('2026-03-20'),
            clockIn: '18:00',
            clockOut: '02:00',
            formStatus: 'approved',
            scheduleStatus: 'approved',
        );

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.create'))
            ->post(route('schedules.store'), [
                'location_id' => $location->id,
                'shift_date' => '2026-03-21',
                'roster' => [
                    $staff->id => [
                        'selected' => '1',
                        'clock_in' => '01:00',
                        'clock_out' => '05:00',
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('schedules.create'))
            ->assertSessionHasErrors('roster');

        $this->assertSame(1, Schedule::query()->where('user_id', $staff->id)->count());
    }

    public function test_manager_can_add_a_non_overlapping_line_for_the_same_staff_and_day(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        [$form] = $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: $shiftDate,
            clockIn: '09:00',
            clockOut: '12:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.form', ['form_id' => $form->id]))
            ->post(route('schedules.form.add-line'), [
                'form_id' => $form->id,
                'user_id' => $staff->id,
                'clock_in' => '13:00',
                'clock_out' => '17:00',
            ]);

        $response
            ->assertRedirect(route('schedules.form', ['form_id' => $form->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Schedule::query()
            ->where('schedule_form_id', $form->id)
            ->where('user_id', $staff->id)
            ->count());
    }

    public function test_manager_can_add_an_overnight_line_to_a_form(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        [$form] = $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: $shiftDate,
            clockIn: '09:00',
            clockOut: '12:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.form', ['form_id' => $form->id]))
            ->post(route('schedules.form.add-line'), [
                'form_id' => $form->id,
                'user_id' => $staff->id,
                'clock_in' => '18:00',
                'clock_out' => '02:00',
            ]);

        $response
            ->assertRedirect(route('schedules.form', ['form_id' => $form->id]))
            ->assertSessionHasNoErrors();

        $overnightLine = Schedule::query()
            ->where('schedule_form_id', $form->id)
            ->where('user_id', $staff->id)
            ->orderByDesc('starts_at')
            ->firstOrFail();

        $this->assertSame('2026-03-20 18:00', $overnightLine->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-03-21 02:00', $overnightLine->ends_at->format('Y-m-d H:i'));
    }

    public function test_form_detail_add_staff_dropdown_includes_staff_already_scheduled_for_that_day(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        [$form] = $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: $shiftDate,
            clockIn: '09:00',
            clockOut: '12:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        $response = $this
            ->actingAs($manager)
            ->get(route('schedules.form', ['form_id' => $form->id]));

        $response
            ->assertOk()
            ->assertSee('Add Staff Line')
            ->assertSee('option value="' . $staff->id . '"', false);
    }

    public function test_submitted_form_shows_already_submitted_footer_state(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        [$form] = $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: $shiftDate,
            clockIn: '09:00',
            clockOut: '12:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        $response = $this
            ->actingAs($manager)
            ->get(route('schedules.form', ['form_id' => $form->id]));

        $response
            ->assertOk()
            ->assertSee('Already Submitted')
            ->assertSee('already in the approval queue', false)
            ->assertDontSee('Submit for Re-Approval');
    }

    public function test_form_detail_groups_lines_by_staff_id_order(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staffA = $this->createStaff($location, 'Alice Server', '25001');
        $staffB = $this->createStaff($location, 'Bob Worker', '25002');
        $shiftDate = Carbon::parse('2026-03-20');

        $form = ScheduleForm::create([
            'location_id' => $location->id,
            'shift_date' => $shiftDate->toDateString(),
            'created_by' => $manager->id,
            'status' => 'submitted',
        ]);

        Schedule::create([
            'schedule_form_id' => $form->id,
            'reapproval_cycle' => 1,
            'user_id' => $staffB->id,
            'location_id' => $location->id,
            'shift_date' => $shiftDate->toDateString(),
            'starts_at' => $shiftDate->copy()->setTime(6, 0),
            'ends_at' => $shiftDate->copy()->setTime(10, 0),
            'status' => 'submitted',
            'change_type' => 'original',
            'created_by' => $manager->id,
        ]);

        Schedule::create([
            'schedule_form_id' => $form->id,
            'reapproval_cycle' => 1,
            'user_id' => $staffA->id,
            'location_id' => $location->id,
            'shift_date' => $shiftDate->toDateString(),
            'starts_at' => $shiftDate->copy()->setTime(8, 0),
            'ends_at' => $shiftDate->copy()->setTime(11, 0),
            'status' => 'submitted',
            'change_type' => 'original',
            'created_by' => $manager->id,
        ]);

        Schedule::create([
            'schedule_form_id' => $form->id,
            'reapproval_cycle' => 1,
            'user_id' => $staffB->id,
            'location_id' => $location->id,
            'shift_date' => $shiftDate->toDateString(),
            'starts_at' => $shiftDate->copy()->setTime(13, 0),
            'ends_at' => $shiftDate->copy()->setTime(18, 0),
            'status' => 'submitted',
            'change_type' => 'original',
            'created_by' => $manager->id,
        ]);

        Schedule::create([
            'schedule_form_id' => $form->id,
            'reapproval_cycle' => 1,
            'user_id' => $staffA->id,
            'location_id' => $location->id,
            'shift_date' => $shiftDate->toDateString(),
            'starts_at' => $shiftDate->copy()->setTime(15, 0),
            'ends_at' => $shiftDate->copy()->setTime(18, 0),
            'status' => 'submitted',
            'change_type' => 'original',
            'created_by' => $manager->id,
        ]);

        $response = $this
            ->actingAs($manager)
            ->get(route('schedules.form', ['form_id' => $form->id]));

        $response->assertOk();

        $orderedUserIds = $response->viewData('schedules')->pluck('user_id')->all();

        $this->assertSame([
            $staffA->id,
            $staffA->id,
            $staffB->id,
            $staffB->id,
        ], $orderedUserIds);
    }

    public function test_manager_cannot_add_an_overlapping_line_for_the_same_staff_and_day(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        [$form] = $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: $shiftDate,
            clockIn: '09:00',
            clockOut: '12:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.form', ['form_id' => $form->id]))
            ->post(route('schedules.form.add-line'), [
                'form_id' => $form->id,
                'user_id' => $staff->id,
                'clock_in' => '11:30',
                'clock_out' => '14:00',
            ]);

        $response
            ->assertRedirect(route('schedules.form', ['form_id' => $form->id]))
            ->assertSessionHasErrors('schedule');

        $this->assertSame(1, Schedule::query()
            ->where('schedule_form_id', $form->id)
            ->where('user_id', $staff->id)
            ->count());
    }

    public function test_manager_cannot_update_a_shift_to_overlap_another_shift_for_the_same_day(): void
    {
        $location = Location::create(['name' => 'Downtown Grill']);
        $manager = $this->createManager($location);
        $staff = $this->createStaff($location, 'Alice Server', '1001');
        $shiftDate = Carbon::parse('2026-03-20');

        [, $morningShift] = $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: $shiftDate,
            clockIn: '09:00',
            clockOut: '12:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        [$formTwo, $afternoonShift] = $this->createFormWithSchedule(
            creator: $manager,
            location: $location,
            staff: $staff,
            shiftDate: $shiftDate,
            clockIn: '13:00',
            clockOut: '17:00',
            formStatus: 'submitted',
            scheduleStatus: 'submitted',
        );

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.form', ['form_id' => $formTwo->id]))
            ->put(route('schedules.update', $afternoonShift), [
                'clock_in' => '11:30',
                'clock_out' => '17:00',
                'notes' => '',
            ]);

        $response
            ->assertRedirect(route('schedules.form', ['form_id' => $formTwo->id]))
            ->assertSessionHasErrors('schedule');

        $this->assertSame('13:00', $afternoonShift->fresh()->starts_at->format('H:i'));
        $this->assertSame('09:00', $morningShift->fresh()->starts_at->format('H:i'));
    }

    public function test_kiosk_clock_in_matches_the_current_split_shift_instead_of_an_earlier_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-20 17:30:00'));

        try {
            $location = Location::create(['name' => 'Downtown Grill']);
            $manager = $this->createManager($location);
            $staff = User::factory()->create([
                'name' => 'Alice Server',
                'staff_id' => '25000',
                'role' => 'staff',
                'location_id' => $location->id,
                'is_active' => true,
                'pin_enabled' => true,
                'pin_hash' => Hash::make('2500'),
                'requires_schedule_for_clock' => true,
            ]);

            $token = 'test-kiosk-token';
            Kiosk::create([
                'name' => 'Front Kiosk',
                'location_id' => $location->id,
                'kiosk_token_hash' => hash('sha256', $token),
                'is_active' => true,
            ]);

            [, $lunchShift] = $this->createFormWithSchedule(
                creator: $manager,
                location: $location,
                staff: $staff,
                shiftDate: Carbon::today(),
                clockIn: '11:00',
                clockOut: '14:00',
                formStatus: 'approved',
                scheduleStatus: 'approved',
            );

            [, $dinnerShift] = $this->createFormWithSchedule(
                creator: $manager,
                location: $location,
                staff: $staff,
                shiftDate: Carbon::today(),
                clockIn: '17:00',
                clockOut: '21:00',
                formStatus: 'approved',
                scheduleStatus: 'approved',
            );

            $response = $this->withHeaders([
                'X-KIOSK-TOKEN' => $token,
            ])->postJson(route('kiosk.clock-in'), [
                'staff_id' => '25000',
                'pin' => '2500',
            ]);

            $response->assertOk();

            $punch = TimePunch::query()->firstOrFail();
            $this->assertSame($dinnerShift->id, $punch->schedule_id);
            $this->assertNotSame($lunchShift->id, $punch->schedule_id);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_kiosk_clock_in_matches_an_overnight_shift_started_the_previous_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-21 01:30:00'));

        try {
            $location = Location::create(['name' => 'Downtown Grill']);
            $manager = $this->createManager($location);
            $staff = User::factory()->create([
                'name' => 'Alice Server',
                'staff_id' => '25000',
                'role' => 'staff',
                'location_id' => $location->id,
                'is_active' => true,
                'pin_enabled' => true,
                'pin_hash' => Hash::make('2500'),
                'requires_schedule_for_clock' => true,
            ]);

            $token = 'test-kiosk-token';
            Kiosk::create([
                'name' => 'Front Kiosk',
                'location_id' => $location->id,
                'kiosk_token_hash' => hash('sha256', $token),
                'is_active' => true,
            ]);

            [, $overnightShift] = $this->createFormWithSchedule(
                creator: $manager,
                location: $location,
                staff: $staff,
                shiftDate: Carbon::parse('2026-03-20'),
                clockIn: '18:00',
                clockOut: '02:00',
                formStatus: 'approved',
                scheduleStatus: 'approved',
            );

            $response = $this->withHeaders([
                'X-KIOSK-TOKEN' => $token,
            ])->postJson(route('kiosk.clock-in'), [
                'staff_id' => '25000',
                'pin' => '2500',
            ]);

            $response->assertOk();

            $punch = TimePunch::query()->firstOrFail();
            $this->assertSame($overnightShift->id, $punch->schedule_id);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createManager(Location $location): User
    {
        return User::factory()->create([
            'role' => 'manager',
            'location_id' => $location->id,
            'can_create_schedules' => true,
        ]);
    }

    private function createStaff(Location $location, string $name, string $staffId): User
    {
        return User::factory()->create([
            'name' => $name,
            'staff_id' => $staffId,
            'role' => 'staff',
            'location_id' => $location->id,
            'is_active' => true,
        ]);
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
