<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\DeviceToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDeviceToken
{
    /**
     * Handle an incoming request.
     *
     * Validates the device token from the X-Device-Token header,
     * updates its last_used_at timestamp, and merges the associated
     * pond into the request for downstream controllers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Device-Token');

        if (! $token) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Device token is required.',
            ], 401);
        }

        $deviceToken = DeviceToken::with('pond')
            ->where('token', $token)
            ->where('is_active', true)
            ->first();

        if (! $deviceToken) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid or inactive device token.',
            ], 401);
        }

        // Update last used timestamp
        $deviceToken->update(['last_used_at' => now()]);

        // Merge the pond into the request for downstream use
        $request->merge(['pond' => $deviceToken->pond]);

        return $next($request);
    }
}
