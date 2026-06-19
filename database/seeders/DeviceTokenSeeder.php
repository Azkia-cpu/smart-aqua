<?php

namespace Database\Seeders;

use App\Models\DeviceToken;
use App\Models\Pond;
use Illuminate\Database\Seeder;

class DeviceTokenSeeder extends Seeder
{
    /**
     * Seed the device_tokens table.
     */
    public function run(): void
    {
        $ponds = Pond::all();

        $tokens = [
            'pond_a' => [
                'token' => 'smartaqua_pond_a_token_2026',
                'device_name' => 'ESP32-KolamA',
            ],
            'pond_b' => [
                'token' => 'smartaqua_pond_b_token_2026',
                'device_name' => 'ESP32-KolamB',
            ],
            'pond_c' => [
                'token' => 'smartaqua_pond_c_token_2026',
                'device_name' => 'ESP32-KolamC',
            ],
            'pond_d' => [
                'token' => 'smartaqua_pond_d_token_2026',
                'device_name' => 'ESP32-KolamD',
            ],
        ];

        foreach ($ponds as $pond) {
            if (isset($tokens[$pond->code])) {
                DeviceToken::create([
                    'pond_id' => $pond->id,
                    'token' => $tokens[$pond->code]['token'],
                    'device_name' => $tokens[$pond->code]['device_name'],
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);
            }
        }
    }
}
