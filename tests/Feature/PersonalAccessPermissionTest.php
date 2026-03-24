<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalAccessPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_to_the_next_allowed_page_when_dashboard_access_is_disabled(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'can_view_dashboard' => false,
            'can_use_web_clock' => true,
            'can_view_my_punches' => false,
            'can_view_punch_summary' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertRedirect(route('clock.index'));
    }

    public function test_clock_page_requires_clock_permission(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'can_use_web_clock' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('clock.index'));

        $response->assertForbidden();
    }

    public function test_punch_log_requires_my_punch_access_when_manager_has_no_team_oversight_permission(): void
    {
        $location = Location::create([
            'name' => 'Downtown Grill',
        ]);

        $user = User::factory()->create([
            'role' => 'manager',
            'location_id' => $location->id,
            'can_view_my_punches' => false,
            'can_view_current_staff' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('punches.index'));

        $response->assertForbidden();
    }

    public function test_punch_summary_requires_permission(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'can_view_punch_summary' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('punches.summary'));

        $response->assertForbidden();
    }
}
