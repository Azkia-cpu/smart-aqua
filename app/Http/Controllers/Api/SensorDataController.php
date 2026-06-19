<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSensorDataRequest;
use App\Services\SensorService;
use Illuminate\Http\JsonResponse;

class SensorDataController extends Controller
{
    public function __construct(
        private readonly SensorService $sensorService,
    ) {}

    /**
     * Store a new sensor data reading from an ESP32 device.
     *
     * The pond is resolved and injected by the ValidateDeviceToken middleware
     * via the request's merged attributes.
     */
    public function store(StoreSensorDataRequest $request): JsonResponse
    {
        $pond = $request->pond;

        $reading = $this->sensorService->storeReading(
            pond: $pond,
            data: $request->validated(),
        );

        $pumpStatus = $pond->currentPumpStatus();

        return response()->json([
            'success' => true,
            'message' => 'Sensor data stored successfully.',
            'data' => [
                'reading' => [
                    'id' => $reading->id,
                    'water_level' => $reading->water_level,
                    'ph_value' => $reading->ph_value,
                    'flow_rate' => $reading->flow_rate,
                    'distance_cm' => $reading->distance_cm,
                    'recorded_at' => $reading->created_at->toIso8601String(),
                ],
                'pump' => [
                    'is_on' => $pumpStatus ? (bool) $pumpStatus->is_on : false,
                    'is_manual_mode' => $pumpStatus ? (bool) $pumpStatus->is_manual_mode : false,
                    'trigger_reason' => $pumpStatus ? $pumpStatus->trigger_reason : null,
                    'min_water_level' => (float) $pond->min_water_level,
                    'max_water_level' => (float) $pond->max_water_level,
                ],
            ],
        ], 201);
    }
}
