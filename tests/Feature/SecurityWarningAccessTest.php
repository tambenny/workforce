<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\SecurityWarning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityWarningAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_view_security_warnings_across_all_locations(): void
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
            'can_view_security_warnings' => true,
        ]);

        $warehouseUser = User::factory()->create([
            'name' => 'Alice Worker',
            'role' => 'staff',
            'location_id' => $warehouse->id,
        ]);

        $showroomUser = User::factory()->create([
            'name' => 'Bob Worker',
            'role' => 'staff',
            'location_id' => $showroom->id,
        ]);

        SecurityWarning::create([
            'user_id' => $warehouseUser->id,
            'location_id' => $warehouse->id,
            'warning_type' => 'late_clock_in',
            'ip_address' => '192.168.1.10',
            'message' => 'Late clock in detected.',
        ]);

        SecurityWarning::create([
            'user_id' => $showroomUser->id,
            'location_id' => $showroom->id,
            'warning_type' => 'vpn_usage',
            'ip_address' => '192.168.1.11',
            'message' => 'VPN usage detected.',
        ]);

        $response = $this
            ->actingAs($hr)
            ->get(route('reports.security-warnings'));

        $response
            ->assertOk()
            ->assertSee('Security Warnings')
            ->assertSee('Warehouse')
            ->assertSee('Showroom')
            ->assertSee('Alice Worker')
            ->assertSee('Bob Worker')
            ->assertSee('Late clock in detected.')
            ->assertSee('VPN usage detected.');
    }
}
