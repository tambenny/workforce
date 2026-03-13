<?php

namespace Database\Seeders;

use App\Models\Kiosk;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KioskSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Location::where('name', 'Warehouse')->first();

        if (! $warehouse) {
            $this->command?->error('Warehouse location not found. Run LocationSeeder first.');
            return;
        }

        $plainToken = Str::random(48);

        Kiosk::updateOrCreate(
            ['name' => 'Warehouse Front Door iPad'],
            [
                'location_id' => $warehouse->id,
                'kiosk_token_hash' => hash('sha256', $plainToken),
                'is_active' => true,
            ]
        );

        $this->command?->warn('Sample kiosk seeded for Warehouse.');
        $this->command?->line('KIOSK TOKEN (save securely, shown once): ' . $plainToken);
        $this->command?->warn('Store this token in kiosk config. DB stores hash only.');
    }
}
