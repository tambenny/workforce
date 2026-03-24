<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_admin_account_without_staff_id_or_pin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('staff.store'), [
                'name' => 'Operations Admin',
                'email' => 'ops-admin@example.com',
                'password' => 'Password123!',
                'role' => 'admin',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('staff.index'));

        $createdAdmin = User::where('email', 'ops-admin@example.com')->first();

        $this->assertNotNull($createdAdmin);
        $this->assertSame('admin', $createdAdmin->role);
        $this->assertNull($createdAdmin->staff_id);
        $this->assertFalse($createdAdmin->pin_enabled);
        $this->assertFalse($createdAdmin->requires_schedule_for_clock);
        $this->assertTrue($createdAdmin->can_view_dashboard);
        $this->assertTrue($createdAdmin->can_use_web_clock);
        $this->assertTrue($createdAdmin->can_view_my_punches);
        $this->assertTrue($createdAdmin->can_view_punch_summary);
        $this->assertTrue($createdAdmin->can_view_schedules);
        $this->assertTrue($createdAdmin->can_create_schedules);
        $this->assertTrue($createdAdmin->can_approve_schedules);
        $this->assertTrue($createdAdmin->can_view_schedule_summary);
        $this->assertTrue($createdAdmin->can_view_current_staff);
        $this->assertTrue($createdAdmin->can_view_punch_photos);
        $this->assertTrue($createdAdmin->can_view_security_warnings);
    }

    public function test_admin_can_create_a_manager_with_menu_permissions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $location = Location::create([
            'name' => 'Downtown Grill',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('staff.store'), [
                'name' => 'Restaurant Manager',
                'email' => 'manager@example.com',
                'staff_id' => '25001',
                'password' => 'Password123!',
                'pin' => '1234',
                'role' => 'manager',
                'location_id' => $location->id,
                'is_active' => '1',
                'requires_schedule_for_clock' => '1',
                'can_view_dashboard' => '1',
                'can_use_web_clock' => '1',
                'can_view_my_punches' => '1',
                'can_view_punch_summary' => '1',
                'can_view_schedules' => '1',
                'can_create_schedules' => '1',
                'can_view_schedule_summary' => '1',
                'can_view_current_staff' => '1',
            ]);

        $response->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'manager@example.com',
            'role' => 'manager',
            'location_id' => $location->id,
            'can_view_dashboard' => true,
            'can_use_web_clock' => true,
            'can_view_my_punches' => true,
            'can_view_punch_summary' => true,
            'can_view_schedules' => true,
            'can_create_schedules' => true,
            'can_view_schedule_summary' => true,
            'can_view_current_staff' => true,
            'can_approve_schedules' => false,
            'can_view_punch_photos' => false,
            'can_view_security_warnings' => false,
        ]);
    }

    public function test_admin_can_create_an_hr_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $location = Location::create([
            'name' => 'Downtown Grill',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('staff.store'), [
                'name' => 'HR Lead',
                'email' => 'hr.lead@example.com',
                'staff_id' => '26001',
                'password' => 'Password123!',
                'pin' => '1234',
                'role' => 'hr',
                'location_id' => $location->id,
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('staff.index'));

        $createdHr = User::where('email', 'hr.lead@example.com')->first();

        $this->assertNotNull($createdHr);
        $this->assertSame('hr', $createdHr->role);
        $this->assertTrue($createdHr->pin_enabled);
        $this->assertFalse($createdHr->can_view_schedules);
        $this->assertFalse($createdHr->can_create_schedules);
        $this->assertFalse($createdHr->can_approve_schedules);
        $this->assertFalse($createdHr->can_view_security_warnings);
    }

    public function test_admin_can_create_an_hr_account_with_schedule_permissions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $location = Location::create([
            'name' => 'Downtown Grill',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('staff.store'), [
                'name' => 'HR Scheduler',
                'email' => 'hr.scheduler@example.com',
                'staff_id' => '26002',
                'password' => 'Password123!',
                'pin' => '1234',
                'role' => 'hr',
                'location_id' => $location->id,
                'is_active' => '1',
                'can_view_schedules' => '1',
                'can_create_schedules' => '1',
                'can_approve_schedules' => '1',
                'can_view_schedule_summary' => '1',
            ]);

        $response->assertRedirect(route('staff.index'));

        $createdHr = User::where('email', 'hr.scheduler@example.com')->first();

        $this->assertNotNull($createdHr);
        $this->assertTrue($createdHr->can_view_schedules);
        $this->assertTrue($createdHr->can_create_schedules);
        $this->assertTrue($createdHr->can_approve_schedules);
        $this->assertTrue($createdHr->can_view_schedule_summary);
        $this->assertTrue($createdHr->canViewSchedules());
        $this->assertTrue($createdHr->hasSchedulePermission('create'));
        $this->assertTrue($createdHr->hasSchedulePermission('approve'));
        $this->assertTrue($createdHr->canViewScheduleSummary());
    }

    public function test_admin_can_create_an_hr_account_with_manager_permissions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $location = Location::create([
            'name' => 'Downtown Grill',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('staff.store'), [
                'name' => 'HR Operations',
                'email' => 'hr.operations@example.com',
                'staff_id' => '26003',
                'password' => 'Password123!',
                'pin' => '1234',
                'role' => 'hr',
                'location_id' => $location->id,
                'is_active' => '1',
                'can_view_dashboard' => '1',
                'can_use_web_clock' => '1',
                'can_view_my_punches' => '1',
                'can_view_punch_summary' => '1',
                'can_view_current_staff' => '1',
                'can_view_punch_photos' => '1',
                'can_view_security_warnings' => '1',
            ]);

        $response->assertRedirect(route('staff.index'));

        $createdHr = User::where('email', 'hr.operations@example.com')->first();

        $this->assertNotNull($createdHr);
        $this->assertTrue($createdHr->canViewDashboard());
        $this->assertTrue($createdHr->canUseWebClock());
        $this->assertTrue($createdHr->canViewOwnPunches());
        $this->assertTrue($createdHr->canViewPunchSummary());
        $this->assertTrue($createdHr->canViewCurrentStaffReport());
        $this->assertTrue($createdHr->canViewPunchPhotos());
        $this->assertTrue($createdHr->canViewSecurityWarnings());
    }

    public function test_hr_cannot_create_an_admin_account(): void
    {
        $hr = User::factory()->create([
            'role' => 'hr',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($hr)
            ->from(route('staff.create'))
            ->post(route('staff.store'), [
                'name' => 'Blocked Admin',
                'email' => 'blocked-admin@example.com',
                'password' => 'Password123!',
                'role' => 'admin',
                'is_active' => '1',
            ]);

        $response
            ->assertRedirect(route('staff.create'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked-admin@example.com',
        ]);
    }

    public function test_hr_can_manage_positions(): void
    {
        $hr = User::factory()->create([
            'role' => 'hr',
            'is_active' => true,
        ]);

        $position = Position::create([
            'name' => 'Line Cook',
            'description' => 'Kitchen station coverage',
            'is_active' => true,
        ]);

        $this->actingAs($hr)
            ->get(route('positions.index'))
            ->assertOk()
            ->assertSee('Positions');

        $this->actingAs($hr)
            ->get(route('positions.create'))
            ->assertOk();

        $this->actingAs($hr)
            ->post(route('positions.store'), [
                'name' => 'Shift Lead',
                'description' => 'Coordinates floor coverage',
                'is_active' => '1',
            ])
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseHas('positions', [
            'name' => 'Shift Lead',
            'is_active' => true,
        ]);

        $this->actingAs($hr)
            ->put(route('positions.update', $position), [
                'name' => 'Senior Line Cook',
                'description' => 'Updated kitchen station coverage',
                'is_active' => '1',
            ])
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'name' => 'Senior Line Cook',
        ]);
    }

    public function test_changing_a_manager_to_staff_clears_manager_only_permissions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $location = Location::create([
            'name' => 'Downtown Grill',
        ]);

        $manager = User::factory()->create([
            'role' => 'manager',
            'location_id' => $location->id,
            'can_view_schedules' => true,
            'can_create_schedules' => true,
            'can_approve_schedules' => true,
            'can_view_schedule_summary' => true,
            'can_view_current_staff' => true,
            'can_view_punch_photos' => true,
            'can_view_security_warnings' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('staff.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'staff_id' => $manager->staff_id ?: '25002',
                'role' => 'staff',
                'location_id' => $location->id,
                'is_active' => '1',
                'requires_schedule_for_clock' => '1',
                'can_view_dashboard' => '1',
                'can_use_web_clock' => '1',
            ]);

        $response->assertRedirect(route('staff.index'));

        $manager->refresh();

        $this->assertSame('staff', $manager->role);
        $this->assertTrue($manager->can_view_dashboard);
        $this->assertTrue($manager->can_use_web_clock);
        $this->assertFalse($manager->can_view_my_punches);
        $this->assertFalse($manager->can_view_punch_summary);
        $this->assertFalse($manager->can_view_schedules);
        $this->assertFalse($manager->can_create_schedules);
        $this->assertFalse($manager->can_approve_schedules);
        $this->assertFalse($manager->can_view_schedule_summary);
        $this->assertFalse($manager->can_view_current_staff);
        $this->assertFalse($manager->can_view_punch_photos);
        $this->assertFalse($manager->can_view_security_warnings);
    }
}
