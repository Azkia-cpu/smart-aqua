<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pond;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PumpControlApiController extends Controller
{
    /**
     * Return the current pump status for an ESP32 device to read.
     *
     * The pond is resolved via the ValidateDeviceToken middleware.
     * The pondCode parameter provides an additional safety check.
     */
    public function status(Request $request, string $pondCode): JsonResponse
    {
        $pond = Pond::where('code', $pondCode)->firstOrFail();

        // Ensure the device token's pond matches the requested pond
        if ($request->pond->id !== $pond->id) {
            return response()->json([
                'success' => false,
                'message' => 'Device token does not match the requested pond.',
            ], 403);
        }

        $pumpStatus = $pond->currentPumpStatus();

        if (! $pumpStatus) {
            return response()->json([
                'success' => true,
                'data' => [
                    'is_on' => false,
                    'is_manual_mode' => false,
                    'trigger_reason' => null,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_on' => $pumpStatus ? (bool) $pumpStatus->is_on : false,
                'is_manual_mode' => $pumpStatus ? (bool) $pumpStatus->is_manual_mode : false,
                'trigger_reason' => $pumpStatus ? $pumpStatus->trigger_reason : null,
                'min_water_level' => (float) $pond->min_water_level,
                'max_water_level' => (float) $pond->max_water_level,
            ],
        ]);
    }
}
