<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kiosk extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location_id',
        'kiosk_token_hash',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function timePunches(): HasMany
    {
        return $this->hasMany(TimePunch::class);
    }
}
