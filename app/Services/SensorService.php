<?php

namespace App\Services;

use App\Models\Pond;
use App\Models\SensorReading;

class SensorService
{
    public function __construct(
        protected PumpService $pumpService,
        protected NotificationService $notificationService,
        protected MonitoringService $monitoringService,
    ) {}

    /**
     * Store a new sensor reading and trigger automated checks.
     */
    public function storeReading(Pond $pond, array $data): SensorReading
    {
        $reading = $pond->sensorReadings()->create([
            'water_level' => $data['water_level'],
            'ph_value' => $data['ph_value'],
            'flow_rate' => $data['flow_rate'] ?? 0.0,
            'distance_cm' => $data['distance_cm'] ?? 0.0,
            'read_at' => $data['read_at'] ?? now(),
        ]);

        // Check water level against thresholds and control pump if needed
        $this->pumpService->checkAndControlPump($pond, $reading->water_level);

        // Check pH level against thresholds and notify if needed
        $this->notificationService->checkPhLevel($pond, $reading->ph_value);

        // Log to monitoring history
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

        return $reading;
    }
}
