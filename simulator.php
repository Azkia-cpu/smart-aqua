<?php

/**
 * SmartAqua ESP32 Simulator
 * 
 * Script ini mensimulasikan pengiriman data sensor dari ESP32
 * ke REST API Laravel. Jalankan dengan: php simulator.php
 * 
 * Usage:
 *   php simulator.php              # Single reading
 *   php simulator.php --loop       # Continuous (every 5 seconds)
 *   php simulator.php --pond pond_b # Specific pond
 */

$baseUrl = 'http://127.0.0.1:8000/api';

// Device tokens (from DeviceTokenSeeder)
$tokens = [
    'pond_a' => 'smartaqua_pond_a_token_2026',
    'pond_b' => 'smartaqua_pond_b_token_2026',
    'pond_c' => 'smartaqua_pond_c_token_2026',
    'pond_d' => 'smartaqua_pond_d_token_2026',
];

// Parse arguments
$loop = in_array('--loop', $argv);
$pondArg = null;
foreach ($argv as $i => $arg) {
    if ($arg === '--pond' && isset($argv[$i + 1])) {
        $pondArg = $argv[$i + 1];
    }
}

$selectedPond = $pondArg ?? 'pond_a';
$token = $tokens[$selectedPond] ?? $tokens['pond_a'];

echo "=== SmartAqua ESP32 Simulator ===\n";
echo "Pond: {$selectedPond}\n";
echo "Token: {$token}\n";
echo "Mode: " . ($loop ? 'Continuous (5s interval)' : 'Single reading') . "\n";
echo "================================\n\n";

function generateSensorData(): array
{
    // Simulate realistic sensor values
    $waterLevel = round(mt_rand(20, 130) / 10, 1);  // 2.0 - 13.0 cm
    $phValue = round(mt_rand(55, 90) / 10, 1);       // 5.5 - 9.0
    $flowRate = round(mt_rand(0, 50) / 10, 1);        // 0.0 - 5.0 L/min
    $distanceCm = round(15 - $waterLevel, 1);         // HC-SR04: max distance - water level

    return [
        'water_level' => max(0, $waterLevel),
        'ph_value' => $phValue,
        'flow_rate' => $flowRate,
        'distance_cm' => max(0, $distanceCm),
    ];
}

function sendSensorData(string $baseUrl, string $token, array $data): void
{
    $url = $baseUrl . '/sensor-data';
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Device-Token: ' . $token,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $timestamp = date('H:i:s');
    
    if ($error) {
        echo "[{$timestamp}] ERROR: {$error}\n";
        return;
    }

    $result = json_decode($response, true);
    
    if ($httpCode === 201 || $httpCode === 200) {
        echo "[{$timestamp}] ✅ Sent: WL={$data['water_level']}cm, pH={$data['ph_value']}, Flow={$data['flow_rate']}L/min";
        
        if (isset($result['data']['pump'])) {
            $pumpOn = $result['data']['pump']['is_on'] ? 'ON' : 'OFF';
            $mode = $result['data']['pump']['is_manual_mode'] ? 'MANUAL' : 'AUTO';
            echo " | Pump: {$pumpOn} ({$mode})";
        }
        echo "\n";
    } else {
        echo "[{$timestamp}] ❌ HTTP {$httpCode}: {$response}\n";
    }
}

function getPumpStatus(string $baseUrl, string $token, string $pondCode): void
{
    $url = $baseUrl . '/pump-status/' . $pondCode;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'X-Device-Token: ' . $token,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $timestamp = date('H:i:s');
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['data'])) {
        $pumpOn = $result['data']['is_on'] ? 'ON' : 'OFF';
        $mode = $result['data']['is_manual_mode'] ? 'MANUAL' : 'AUTO';
        echo "[{$timestamp}] 🔧 Pump Status: {$pumpOn} ({$mode})\n";
    }
}

// Main loop
do {
    $data = generateSensorData();
    sendSensorData($baseUrl, $token, $data);
    
    // Also check pump status
    getPumpStatus($baseUrl, $token, $selectedPond);
    
    if ($loop) {
        echo "--- Waiting 5 seconds ---\n";
        sleep(5);
    }
} while ($loop);

echo "\nDone!\n";
