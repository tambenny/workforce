<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'staff_id',
        'password',
        'role',
        'can_create_schedules',
        'can_approve_schedules',
        'location_id',
        'position_id',
        'is_active',
        'pin_hash',
        'pin_enabled',
        'requires_schedule_for_clock',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'pin_enabled' => 'boolean',
            'requires_schedule_for_clock' => 'boolean',
            'can_create_schedules' => 'boolean',
            'can_approve_schedules' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function timePunches(): HasMany
    {
        return $this->hasMany(TimePunch::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function hasSchedulePermission(string $ability): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        if ($this->role !== 'manager') {
            return false;
        }

        return match ($ability) {
            'create' => (bool) $this->can_create_schedules,
            'approve' => (bool) $this->can_approve_schedules,
            default => false,
        };
    }
}
