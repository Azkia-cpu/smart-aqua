<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PumpControl extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'pond_id',
        'is_on',
        'is_manual_mode',
        'triggered_by',
        'trigger_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_on' => 'boolean',
            'is_manual_mode' => 'boolean',
        ];
    }

    /**
     * Get the pond that this pump control belongs to.
     */
    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    /**
     * Get the user who triggered this pump control.
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
