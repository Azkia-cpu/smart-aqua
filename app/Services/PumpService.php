<?php

namespace App\Services;

use App\Models\Pond;
use App\Models\PumpControl;

class PumpService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected MonitoringService $monitoringService,
    ) {}

    /**
     * Automatically check water level and control pump accordingly.
     */
    public function checkAndControlPump(Pond $pond, float $waterLevel): void
    {
        $currentStatus = $this->getCurrentStatus($pond);

        // 1. SAFETY OVERRIDE: Water level at or above maximum -> turn pump OFF
        // This applies to BOTH manual and automatic modes for flood safety.
        if ($waterLevel >= $pond->max_water_level) {
            // Only create a new record if pump is not already OFF, or if it is currently in manual mode
            if ($currentStatus?->is_on !== false || $currentStatus?->is_manual_mode) {
                $pumpControl = $pond->pumpControls()->create([
                    'is_on' => false,
                    'is_manual_mode' => false, // Switch back to automatic mode for safety
                    'triggered_by' => null,
                    'trigger_reason' => 'auto_max_level',
                ]);

                $this->notificationService->createNotification(
                    pond: $pond,
                    type: 'danger',
                    title: 'Pompa Dimatikan (Safety Override)',
                    message: "Pompa dimatikan paksa karena level air ({$waterLevel} cm) mencapai/melebihi batas maksimum ({$pond->max_water_level} cm). Mode manual telah dinonaktifkan.",
                );

                $this->monitoringService->logEvent(
                    pond: $pond,
                    eventType: 'pump_off_safety',
                    description: "Pump turned OFF as safety override due to max water level ({$waterLevel} cm). Manual mode disabled.",
                    metadata: [
                        'water_level' => $waterLevel,
                        'threshold' => $pond->max_water_level,
                        'pump_control_id' => $pumpControl->id,
                    ],
                );
            }

            return;
        }

        // If pump is in manual mode, skip automatic control logic for lower levels
        if ($currentStatus?->is_manual_mode) {
            return;
        }

        // 2. Water level below minimum -> turn pump ON
        if ($waterLevel < $pond->min_water_level) {
            // Only create a new record if pump is not already ON
            if (!$currentStatus?->is_on) {
                $pumpControl = $pond->pumpControls()->create([
                    'is_on' => true,
                    'is_manual_mode' => false,
                    'triggered_by' => null,
                    'trigger_reason' => 'auto_low_level',
                ]);

                $this->notificationService->createNotification(
                    pond: $pond,
                    type: 'info',
                    title: 'Pompa Dinyalakan Otomatis',
                    message: "Pompa dinyalakan karena level air ({$waterLevel} cm) di bawah batas minimum ({$pond->min_water_level} cm).",
                );

                $this->monitoringService->logEvent(
                    pond: $pond,
                    eventType: 'pump_on_auto',
                    description: "Pump turned ON automatically due to low water level ({$waterLevel} cm).",
                    metadata: [
                        'water_level' => $waterLevel,
                        'threshold' => $pond->min_water_level,
                        'pump_control_id' => $pumpControl->id,
                    ],
                );
            }

            return;
        }
    }

    /**
     * Manually control the pump (turn on or off).
     */
    public function manualControl(Pond $pond, bool $turnOn, int $userId): PumpControl
    {
        $pumpControl = $pond->pumpControls()->create([
            'is_on' => $turnOn,
            'is_manual_mode' => true,
            'triggered_by' => $userId,
            'trigger_reason' => $turnOn ? 'manual_on' : 'manual_off',
        ]);

        $this->monitoringService->logEvent(
            pond: $pond,
            eventType: $turnOn ? 'pump_on_manual' : 'pump_off_manual',
            description: sprintf(
                'Pump turned %s manually by user ID %d.',
                $turnOn ? 'ON' : 'OFF',
                $userId,
            ),
            metadata: [
                'user_id' => $userId,
                'pump_control_id' => $pumpControl->id,
            ],
        );

        return $pumpControl;
    }

    /**
     * Toggle between manual and automatic pump mode.
     */
    public function toggleManualMode(Pond $pond, bool $isManual, int $userId): PumpControl
    {
        $currentStatus = $this->getCurrentStatus($pond);
        $currentlyOn = $currentStatus?->is_on ?? false;

        $pumpControl = $pond->pumpControls()->create([
            'is_on' => $currentlyOn,
            'is_manual_mode' => $isManual,
            'triggered_by' => $userId,
            'trigger_reason' => $isManual ? 'switch_to_manual' : 'switch_to_auto',
        ]);

        $this->monitoringService->logEvent(
            pond: $pond,
            eventType: $isManual ? 'mode_manual' : 'mode_auto',
            description: sprintf(
                'Pump mode switched to %s by user ID %d.',
                $isManual ? 'MANUAL' : 'AUTO',
                $userId,
            ),
            metadata: [
                'user_id' => $userId,
                'is_manual' => $isManual,
                'pump_control_id' => $pumpControl->id,
            ],
        );

        // If switching back to auto mode, immediately check current water level
        if (!$isManual) {
            $latestReading = $pond->latestReading();

            if ($latestReading) {
                $this->checkAndControlPump($pond, $latestReading->water_level);
            }
        }

        return $pumpControl;
    }

    /**
     * Get the current (latest) pump status for a pond.
     */
    public function getCurrentStatus(Pond $pond): ?PumpControl
    {
        return $pond->pumpControls()->latest('id')->first();
    }
}
