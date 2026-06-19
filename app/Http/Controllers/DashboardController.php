<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PumpControlRequest;
use App\Models\PondNotification;
use App\Models\Pond;
use App\Models\SensorReading;
use App\Services\MonitoringService;
use App\Services\NotificationService;
use App\Services\PumpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private readonly MonitoringService $monitoringService,
        private readonly PumpService $pumpService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Display the main dashboard view.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $ponds = $user->is_admin
            ? Pond::orderBy('name')->get()
            : Pond::where('user_id', $user->id)->orderBy('name')->get();

        // Fallback: If a non-admin user has no specifically assigned ponds,
        // let them see all active ponds so they can see the system working.
        if (!$user->is_admin && $ponds->isEmpty()) {
            $ponds = Pond::where('is_active', true)->orderBy('name')->get();
        }

        $selectedPondCode = $request->query('pond');
        $currentPond = $selectedPondCode
            ? $ponds->firstWhere('code', $selectedPondCode)
            : $ponds->first();

        return view('dashboard.index', [
            'ponds' => $ponds,
            'currentPond' => $currentPond,
        ]);
    }

    /**
     * Get the latest sensor reading and pump status for a pond (AJAX).
     */
    public function latestData(string $pondCode): JsonResponse
    {
        $pond = Pond::where('code', $pondCode)->firstOrFail();

        $latestReading = $pond->sensorReadings()->latest('read_at')->first();
        $pumpStatus = $this->pumpService->getCurrentStatus($pond);

        return response()->json([
            'success' => true,
            'data' => [
                'reading' => $latestReading,
                'pump' => $pumpStatus,
                'pond' => [
                    'name' => $pond->name,
                    'code' => $pond->code,
                    'min_water_level' => $pond->min_water_level,
                    'max_water_level' => $pond->max_water_level,
                    'min_ph' => $pond->min_ph,
                    'max_ph' => $pond->max_ph,
                ],
            ],
        ]);
    }

    /**
     * Get 48-hour chart data for a pond (AJAX).
     */
    public function chartData(string $pondCode): JsonResponse
    {
        $pond = Pond::where('code', $pondCode)->firstOrFail();

        $chartData = $this->monitoringService->getChartData(
            pond: $pond,
            hours: 48,
        );

        return response()->json([
            'success' => true,
            'data' => $chartData,
        ]);
    }

    /**
     * Get the latest notifications for a pond (AJAX).
     */
    public function notifications(string $pondCode): JsonResponse
    {
        $pond = Pond::where('code', $pondCode)->firstOrFail();

        $notifications = $this->notificationService->getLatestNotifications($pond, 10);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get paginated monitoring history for a pond (AJAX).
     */
    public function history(string $pondCode): JsonResponse
    {
        $pond = Pond::where('code', $pondCode)->firstOrFail();

        $history = $pond->monitoringHistories()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Control the pump for a given pond (AJAX).
     */
    public function pumpControl(PumpControlRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $pond = Pond::where('code', $validated['pond_code'])->firstOrFail();
        $action = $validated['action'];
        $userId = Auth::id();

        $pumpStatus = match ($action) {
            'on' => $this->pumpService->manualControl($pond, true, $userId),
            'off' => $this->pumpService->manualControl($pond, false, $userId),
            'toggle_manual_on' => $this->pumpService->toggleManualMode($pond, true, $userId),
            'toggle_manual_off' => $this->pumpService->toggleManualMode($pond, false, $userId),
        };

        return response()->json([
            'success' => true,
            'message' => 'Status pompa berhasil diperbarui.',
            'data' => $pumpStatus,
        ]);
    }

    /**
     * Mark a notification as read (AJAX).
     */
    public function markNotificationRead(int $id): JsonResponse
    {
        $this->notificationService->markAsRead($id);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai telah dibaca.',
        ]);
    }
}
