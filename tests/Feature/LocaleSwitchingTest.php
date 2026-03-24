<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_chinese_locale_in_session(): void
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

        $response = $this
            ->actingAs($manager)
            ->from(route('schedules.create'))
            ->post(route('locale.update'), [
                'locale' => 'zh_CN',
            ]);

        $response
            ->assertRedirect(route('schedules.create'))
            ->assertSessionHas('locale', 'zh_CN');
    }

    public function test_create_schedule_page_renders_chinese_when_locale_is_set_in_session(): void
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

        $response = $this
            ->actingAs($manager)
            ->withSession(['locale' => 'zh_CN'])
            ->get(route('schedules.create'));

        $response
            ->assertOk()
            ->assertSee('排班规划')
            ->assertSee('创建排班')
            ->assertSee('返回排班')
            ->assertSee('星期');
    }
}
