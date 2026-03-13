<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Server / 前场', 'description' => 'Serve customers directly in front-of-house'],
            ['name' => 'Cook / 厨师', 'description' => 'Kitchen cook role'],
            ['name' => 'Washing Worker / 洗碗', 'description' => 'Worker responsible for dishwashing and cleaning'],
            ['name' => 'Cashier / 收银', 'description' => 'Checkout and register operations'],
            ['name' => 'Manager / 经理', 'description' => 'General manager role'],
            ['name' => 'Store Manager / 店长', 'description' => 'Store-level manager'],
        ];

        DB::transaction(function () use ($positions): void {
            $canonicalByKey = [];
            foreach ($positions as $p) {
                $canonicalByKey[$this->normalizeEnglishKey($p['name'])] = $p;
            }

            foreach ($canonicalByKey as $key => $canonical) {
                $target = Position::firstOrCreate(
                    ['name' => $canonical['name']],
                    ['description' => $canonical['description'], 'is_active' => true]
                );

                $target->update([
                    'description' => $canonical['description'],
                    'is_active' => true,
                ]);

                $all = Position::orderBy('id')->get();
                foreach ($all as $row) {
                    if ($this->normalizeEnglishKey($row->name) !== $key || $row->id === $target->id) {
                        continue;
                    }

                    DB::table('users')
                        ->where('position_id', $row->id)
                        ->update(['position_id' => $target->id]);

                    $row->delete();
                }
            }
        });
    }

    private function normalizeEnglishKey(string $name): string
    {
        $first = trim(explode('/', $name)[0] ?? $name);
        $key = strtolower($first);
        if ($key === 'serer') {
            return 'server';
        }

        return $key;
    }
}
