<?php

namespace Tests\Feature;

use App\Models\Kiosk;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KioskCacheHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiosk_camera_page_is_served_with_no_store_cache_headers(): void
    {
        $token = 'test-kiosk-token';
        $location = Location::create(['name' => 'Downtown Grill']);

        Kiosk::create([
            'name' => 'Front Kiosk',
            'location_id' => $location->id,
            'kiosk_token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'X-KIOSK-TOKEN' => $token,
        ])->get(route('kiosk.camera.home'));

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        $response->assertHeader('Vary', 'Cookie, X-KIOSK-TOKEN');
    }

    public function test_kiosk_token_redirect_is_served_with_no_store_cache_headers(): void
    {
        $token = 'test-kiosk-token';
        $location = Location::create(['name' => 'Downtown Grill']);

        Kiosk::create([
            'name' => 'Front Kiosk',
            'location_id' => $location->id,
            'kiosk_token_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $response = $this->get(route('kiosk.camera.home', ['token' => $token]));

        $response->assertRedirect(route('kiosk.camera.home'));
        $response->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        $response->assertHeader('Vary', 'Cookie, X-KIOSK-TOKEN');
    }
}
