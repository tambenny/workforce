<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'allowed_ip',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function kiosks(): HasMany
    {
        return $this->hasMany(Kiosk::class);
    }

    public function timePunches(): HasMany
    {
        return $this->hasMany(TimePunch::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function securityWarnings(): HasMany
    {
        return $this->hasMany(SecurityWarning::class);
    }
}
