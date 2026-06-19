<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'pond_id',
        'water_level',
        'ph_value',
        'flow_rate',
        'distance_cm',
        'read_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'water_level' => 'float',
            'ph_value' => 'float',
            'flow_rate' => 'float',
            'distance_cm' => 'float',
            'read_at' => 'datetime',
        ];
    }

    /**
     * Get the pond that this reading belongs to.
     */
    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }
}
