<?php

namespace Database\Seeders;

use App\Models\Pond;
use Illuminate\Database\Seeder;

class PondSeeder extends Seeder
{
    /**
     * Seed the ponds table.
     */
    public function run(): void
    {
        $defaults = [
            'min_water_level' => 3.0,
            'max_water_level' => 12.0,
            'min_ph' => 6.5,
            'max_ph' => 8.5,
            'is_active' => true,
        ];

        Pond::create([
            ...$defaults,
            'name' => 'Kolam A',
            'code' => 'pond_a',
            'user_id' => 2,
        ]);

        Pond::create([
            ...$defaults,
            'name' => 'Kolam B',
            'code' => 'pond_b',
            'user_id' => null,
        ]);

        Pond::create([
            ...$defaults,
            'name' => 'Kolam C',
            'code' => 'pond_c',
            'user_id' => null,
        ]);

        Pond::create([
            ...$defaults,
            'name' => 'Kolam D',
            'code' => 'pond_d',
            'user_id' => null,
        ]);
    }
}
