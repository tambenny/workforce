<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Main Office', 'Warehouse', 'Restaurant 1'] as $name) {
            Location::firstOrCreate(
                ['name' => $name],
                ['allowed_ip' => $name === 'Restaurant 1' ? '127.0.0.1' : null]
            );
        }
    }
}
