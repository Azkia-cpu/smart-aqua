<?php

namespace App\Services;

use App\Models\Pond;
use App\Models\SensorReading;
use Illuminate\Support\Facades\Log;

class SensorService
{
    public function __construct(
        protected PumpService $pumpService,
        protected NotificationService $notificationService,
        protected MonitoringService $monitoringService,
    ) {}

    /**
     * Store a new sensor reading and trigger automated checks.
     *
     * Optimized for speed: stores the reading first, then runs
     * secondary tasks (pump check, notifications, logging) with
     * error isolation so a failure in one doesn't block the response.
     */
    public function storeReading(Pond $pond, array $data): SensorReading
    {
        // 1. Store reading immediately (critical path)
        $reading = $pond->sensorReadings()->create([
            'water_level' => $data['water_level'],
            'ph_value' => $data['ph_value'],
            'flow_rate' => $data['flow_rate'] ?? 0.0,
            'distance_cm' => $data['distance_cm'] ?? 0.0,
            'read_at' => $data['read_at'] ?? now(),
        ]);

        // 2. Check water level and control pump (important, but isolated)
        try {
            $this->pumpService->checkAndControlPump($pond, $reading->water_level);
        } catch (\Throwable $e) {
            Log::error('Pump check failed', ['error' => $e->getMessage(), 'pond_id' => $pond->id]);
        }

        // 3. Check pH notifications (with cooldown to prevent email spam)
        try {
            $this->notificationService->checkPhLevel($pond, $reading->ph_value);
        } catch (\Throwable $e) {
            Log::error('pH notification check failed', ['error' => $e->getMessage(), 'pond_id' => $pond->id]);
        }

        // 4. Log to monitoring history (non-critical)
        try {
            $this->monitoringService->logEvent(
                pond: $pond,
                eventType: 'sensor_reading',
                description: "Sensor reading recorded: water_level={$reading->water_level}, pH={$reading->ph_value}, flow_rate={$reading->flow_rate}",
                metadata: [
                    'water_level' => $reading->water_level,
                    'ph_value' => $reading->ph_value,
                    'flow_rate' => $reading->flow_rate,
                    'distance_cm' => $reading->distance_cm,
                    'read_at' => $reading->read_at->toIso8601String(),
                ],
            );
        } catch (\Throwable $e) {
            Log::error('Monitoring log failed', ['error' => $e->getMessage(), 'pond_id' => $pond->id]);
        }

        return $reading;
    }
}

