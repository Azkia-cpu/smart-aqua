<?php

namespace App\Services;

use App\Models\MonitoringHistory;
use App\Models\Pond;
use Illuminate\Database\Eloquent\Collection;

class MonitoringService
{
    /**
     * Log a monitoring event for a pond.
     */
    public function logEvent(Pond $pond, string $eventType, string $description, ?array $metadata = null): MonitoringHistory
    {
        return $pond->monitoringHistories()->create([
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get monitoring history for the last N hours.
     */
    public function getHistory(Pond $pond, int $hours = 48): Collection
    {
        return $pond->monitoringHistories()
            ->where('created_at', '>=', now()->subHours($hours))
            ->latest()
            ->get();
    }

    /**
     * Return sensor readings formatted for Chart.js.
     *
     * @return array{labels: list<string>, water_levels: list<float>, ph_values: list<float>, flow_rates: list<float>}
     */
    public function getChartData(Pond $pond, int $hours = 48): array
    {
        $readings = $pond->sensorReadings()
            ->where('read_at', '>=', now()->subHours($hours))
            ->orderBy('read_at')
            ->get();

        return [
            'labels' => $readings->pluck('read_at')->map(fn ($dt) => $dt->format('Y-m-d H:i'))->values()->all(),
            'water_levels' => $readings->pluck('water_level')->values()->all(),
            'ph_values' => $readings->pluck('ph_value')->values()->all(),
            'flow_rates' => $readings->pluck('flow_rate')->values()->all(),
        ];
    }
}
