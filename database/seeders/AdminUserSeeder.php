<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_ADMIN_PASSWORD', 'ChangeMe123!');

        User::updateOrCreate(
            ['email' => 'admin@lafayettesweets.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make($password),
                'role' => 'admin',
                'can_create_schedules' => true,
                'can_approve_schedules' => true,
                'is_active' => true,
                'pin_enabled' => false,
            ]
        );

        $this->command?->warn('Seeded admin user: admin@lafayettesweets.com');
        $this->command?->warn('Change SEED_ADMIN_PASSWORD and rotate immediately in non-local environments.');
    }
}
