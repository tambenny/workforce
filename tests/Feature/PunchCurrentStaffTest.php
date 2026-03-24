<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\TimePunch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PunchCurrentStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_view_the_current_staff_report(): void
    {
        $location = Location::create([
            'name' => 'Warehouse',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'location_id' => $location->id,
        ]);

        $response = $this
            ->actingAs($staff)
            ->get(route('punches.current'));

        $response->assertForbidden();
    }

    public function test_manager_only_sees_current_staff_for_their_location(): void
    {
        $warehouse = Location::create([
            'name' => 'Warehouse',
        ]);

        $showroom = Location::create([
            'name' => 'Showroom',
        ]);

        $manager = User::factory()->create([
            'name' => 'Manager User',
            'role' => 'manager',
            'location_id' => $warehouse->id,
            'can_view_current_staff' => true,
        ]);

        $warehouseStaff = User::factory()->create([
            'name' => 'Alice Worker',
            'staff_id' => 'A100',
            'role' => 'staff',
            'location_id' => $warehouse->id,
        ]);

        $showroomStaff = User::factory()->create([
            'name' => 'Bob Worker',
            'staff_id' => 'B200',
            'role' => 'staff',
            'location_id' => $showroom->id,
        ]);

        TimePunch::create([
            'user_id' => $warehouseStaff->id,
            'location_id' => $warehouse->id,
            'source' => 'web',
            'clock_in_at' => now()->subHours(2),
        ]);

        TimePunch::create([
            'user_id' => $showroomStaff->id,
            'location_id' => $showroom->id,
            'source' => 'web',
            'clock_in_at' => now()->subHour(),
        ]);

        $response = $this
            ->actingAs($manager)
            ->get(route('punches.current'));

        $response
            ->assertOk()
            ->assertSee('Current Staff')
            ->assertSee('Warehouse')
            ->assertSee('Alice Worker')
            ->assertDontSee('Bob Worker')
            ->assertDontSee('Showroom');
    }

    public function test_manager_needs_current_staff_permission_to_view_the_report(): void
    {
        $location = Location::create([
            'name' => 'Warehouse',
        ]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'location_id' => $location->id,
            'can_view_current_staff' => false,
        ]);

        $response = $this
            ->actingAs($manager)
            ->get(route('punches.current'));

        $response->assertForbidden();
    }

    public function test_admin_can_filter_the_current_staff_report_by_location(): void
    {
        $warehouse = Location::create([
            'name' => 'Warehouse',
        ]);

        $showroom = Location::create([
            'name' => 'Showroom',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $warehouseStaff = User::factory()->create([
            'name' => 'Alice Worker',
            'staff_id' => 'A100',
            'role' => 'staff',
            'location_id' => $warehouse->id,
        ]);

        $showroomStaff = User::factory()->create([
            'name' => 'Bob Worker',
            'staff_id' => 'B200',
            'role' => 'staff',
            'location_id' => $showroom->id,
        ]);

        TimePunch::create([
            'user_id' => $warehouseStaff->id,
            'location_id' => $warehouse->id,
            'source' => 'web',
            'clock_in_at' => now()->subHours(3),
        ]);

        TimePunch::create([
            'user_id' => $showroomStaff->id,
            'location_id' => $showroom->id,
            'source' => 'kiosk',
            'clock_in_at' => now()->subMinutes(40),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('punches.current', ['location_id' => $showroom->id]));

        $response
            ->assertOk()
            ->assertSee('Showroom')
            ->assertSee('Bob Worker')
            ->assertDontSee('Alice Worker')
            ->assertSee('1 staff currently clocked in');
    }

    public function test_hr_can_see_current_staff_across_all_locations(): void
    {
        $warehouse = Location::create([
            'name' => 'Warehouse',
        ]);

        $showroom = Location::create([
            'name' => 'Showroom',
        ]);

        $hr = User::factory()->create([
            'role' => 'hr',
            'location_id' => $warehouse->id,
            'can_view_current_staff' => true,
        ]);

        $warehouseStaff = User::factory()->create([
            'name' => 'Alice Worker',
            'staff_id' => 'A100',
            'role' => 'staff',
            'location_id' => $warehouse->id,
        ]);

        $showroomStaff = User::factory()->create([
            'name' => 'Bob Worker',
            'staff_id' => 'B200',
            'role' => 'staff',
            'location_id' => $showroom->id,
        ]);

        TimePunch::create([
            'user_id' => $warehouseStaff->id,
            'location_id' => $warehouse->id,
            'source' => 'web',
            'clock_in_at' => now()->subHours(2),
        ]);

        TimePunch::create([
            'user_id' => $showroomStaff->id,
            'location_id' => $showroom->id,
            'source' => 'kiosk',
            'clock_in_at' => now()->subMinutes(40),
        ]);

        $response = $this
            ->actingAs($hr)
            ->get(route('punches.current'));

        $response
            ->assertOk()
            ->assertSee('All accessible locations')
            ->assertSee('Warehouse')
            ->assertSee('Showroom')
            ->assertSee('Alice Worker')
            ->assertSee('Bob Worker');
    }
}
