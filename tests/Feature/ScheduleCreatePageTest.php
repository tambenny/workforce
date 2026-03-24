<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduleCreatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_schedule_page_renders_shift_date_from_query_string(): void
    {
        $location = Location::create([
            'name' => 'Downtown Grill',
            'is_active' => true,
        ]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'location_id' => $location->id,
            'can_create_schedules' => true,
            'is_active' => true,
        ]);

        $shiftDate = '2026-03-20';
        $weekday = Carbon::createFromFormat('Y-m-d', $shiftDate)->translatedFormat('l');

        $response = $this
            ->actingAs($manager)
            ->get(route('schedules.create', [
                'location_id' => $location->id,
                'shift_date' => $shiftDate,
            ]));

        $response
            ->assertOk()
            ->assertSee('value="' . $shiftDate . '"', false)
            ->assertSee($shiftDate)
            ->assertSee($weekday);
    }
}
