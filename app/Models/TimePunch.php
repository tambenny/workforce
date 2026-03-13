<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimePunch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'location_id',
        'kiosk_id',
        'schedule_id',
        'source',
        'clock_in_at',
        'clock_out_at',
        'clock_in_photo_path',
        'clock_out_photo_path',
        'ip_address',
        'user_agent',
        'violation_note',
    ];

    protected $casts = [
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function kiosk(): BelongsTo
    {
        return $this->belongsTo(Kiosk::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
