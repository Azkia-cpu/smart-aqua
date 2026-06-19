<?php

namespace App\Services;

use App\Models\Pond;
use App\Models\PondNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification record and optionally send email to the pond owner.
     */
    public function createNotification(Pond $pond, string $type, string $title, string $message): PondNotification
    {
        $notification = $pond->notifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);

        // Send email notification to pond owner if available
        $this->sendEmailNotification($pond, $title, $message);

        return $notification;
    }

    /**
     * Get the latest notifications for a pond.
     */
    public function getLatestNotifications(Pond $pond, int $limit = 10): Collection
    {
        return $pond->notifications()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $notificationId): void
    {
        PondNotification::where('id', $notificationId)->update(['is_read' => true]);
    }

    /**
     * Check pH level and create appropriate notifications.
     */
    public function checkPhLevel(Pond $pond, float $phValue): void
    {
        if ($phValue < 6.5) {
            $this->createNotification(
                pond: $pond,
                type: 'danger',
                title: 'pH Terlalu Rendah',
                message: "Nilai pH ({$phValue}) berada di bawah batas aman (6.5). Segera periksa kondisi air kolam {$pond->name}.",
            );

            return;
        }

        if ($phValue > 8.5) {
            $this->createNotification(
                pond: $pond,
                type: 'danger',
                title: 'pH Terlalu Tinggi',
                message: "Nilai pH ({$phValue}) berada di atas batas aman (8.5). Segera periksa kondisi air kolam {$pond->name}.",
            );

            return;
        }

        if ($phValue >= 6.5 && $phValue < 7.0) {
            $this->createNotification(
                pond: $pond,
                type: 'warning',
                title: 'pH Mendekati Batas Bawah',
                message: "Nilai pH ({$phValue}) mendekati batas bawah aman. Pantau kolam {$pond->name} secara berkala.",
            );

            return;
        }

        if ($phValue > 8.0 && $phValue <= 8.5) {
            $this->createNotification(
                pond: $pond,
                type: 'warning',
                title: 'pH Mendekati Batas Atas',
                message: "Nilai pH ({$phValue}) mendekati batas atas aman. Pantau kolam {$pond->name} secara berkala.",
            );
        }
    }

    /**
     * Send email notification to the pond owner.
     */
    protected function sendEmailNotification(Pond $pond, string $title, string $message): void
    {
        $user = $pond->user;

        if (!$user?->email) {
            return;
        }

        try {
            Mail::to($user->email)->send(
                new \App\Mail\SensorAlertMail(
                    pond: $pond,
                    alertType: 'warning',
                    alertTitle: $title,
                    alertMessage: $message,
                )
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send email notification', [
                'pond_id' => $pond->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
