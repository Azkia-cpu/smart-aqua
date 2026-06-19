<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pond extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'user_id',
        'min_water_level',
        'max_water_level',
        'min_ph',
        'max_ph',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_water_level' => 'float',
            'max_water_level' => 'float',
            'min_ph' => 'float',
            'max_ph' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user that owns this pond.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sensor readings for this pond.
     */
    public function sensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    /**
     * Get the pump controls for this pond.
     */
    public function pumpControls(): HasMany
    {
        return $this->hasMany(PumpControl::class);
    }

    /**
     * Get the notifications for this pond.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(PondNotification::class);
    }

    /**
     * Get the monitoring history for this pond.
     */
    public function monitoringHistories(): HasMany
    {
        return $this->hasMany(MonitoringHistory::class);
    }

    /**
     * Get the device token for this pond.
     */
    public function deviceToken(): HasOne
    {
        return $this->hasOne(DeviceToken::class);
    }

    /**
     * Get the latest sensor reading for this pond.
     */
    public function latestReading(): ?SensorReading
    {
        return $this->sensorReadings()->latest('read_at')->first();
    }

    /**
     * Get the current pump status for this pond.
     */
    public function currentPumpStatus(): ?PumpControl
    {
        return $this->pumpControls()->latest()->first();
    }
}
