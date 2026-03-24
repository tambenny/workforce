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
        'can_view_schedules',
        'can_view_schedule_summary',
        'can_view_current_staff',
        'can_view_punch_photos',
        'can_view_security_warnings',
        'can_view_dashboard',
        'can_use_web_clock',
        'can_view_my_punches',
        'can_view_punch_summary',
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
            'can_view_schedules' => 'boolean',
            'can_view_schedule_summary' => 'boolean',
            'can_view_current_staff' => 'boolean',
            'can_view_punch_photos' => 'boolean',
            'can_view_security_warnings' => 'boolean',
            'can_view_dashboard' => 'boolean',
            'can_use_web_clock' => 'boolean',
            'can_view_my_punches' => 'boolean',
            'can_view_punch_summary' => 'boolean',
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

        if (! in_array($this->role, ['manager', 'hr'], true)) {
            return false;
        }

        return match ($ability) {
            'create' => (bool) $this->can_create_schedules,
            'approve' => (bool) $this->can_approve_schedules,
            default => false,
        };
    }

    public function canViewSchedules(): bool
    {
        if (in_array($this->role, ['admin', 'staff'], true)) {
            return true;
        }

        if (! in_array($this->role, ['manager', 'hr'], true)) {
            return false;
        }

        return (bool) $this->can_view_schedules
            || $this->hasSchedulePermission('create')
            || $this->hasSchedulePermission('approve');
    }

    public function canViewScheduleSummary(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return in_array($this->role, ['manager', 'hr'], true)
            && (bool) $this->can_view_schedule_summary;
    }

    public function canViewScheduleDetails(): bool
    {
        return $this->canViewSchedules() || $this->canViewScheduleSummary();
    }

    public function canViewCurrentStaffReport(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return in_array($this->role, ['manager', 'hr'], true)
            && (bool) $this->can_view_current_staff;
    }

    public function canViewPunchPhotos(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return in_array($this->role, ['manager', 'hr'], true)
            && (bool) $this->can_view_punch_photos;
    }

    public function canViewSecurityWarnings(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return in_array($this->role, ['manager', 'hr'], true)
            && (bool) $this->can_view_security_warnings;
    }

    public function canViewDashboard(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return $this->attributeEnabledByDefault('can_view_dashboard');
    }

    public function canUseWebClock(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return $this->attributeEnabledByDefault('can_use_web_clock');
    }

    public function canViewOwnPunches(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return $this->attributeEnabledByDefault('can_view_my_punches');
    }

    public function canViewPunchSummary(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        return $this->attributeEnabledByDefault('can_view_punch_summary');
    }

    public function canViewPunchLog(): bool
    {
        return $this->canViewOwnPunches() || $this->canViewCurrentStaffReport();
    }

    public function preferredHomeRouteName(): string
    {
        return match (true) {
            $this->canViewDashboard() => 'dashboard',
            $this->canUseWebClock() => 'clock.index',
            $this->canViewPunchLog() => 'punches.index',
            $this->canViewPunchSummary() => 'punches.summary',
            $this->canViewSchedules() => 'schedules.index',
            $this->canViewScheduleSummary() => 'schedules.summary',
            $this->hasSchedulePermission('create') => 'schedules.create',
            $this->hasSchedulePermission('approve') => 'schedules.approvals',
            $this->canViewCurrentStaffReport() => 'punches.current',
            $this->canViewPunchPhotos() => 'punches.photos',
            $this->canViewSecurityWarnings() => 'reports.security-warnings',
            in_array($this->role, ['admin', 'hr'], true) => 'staff.index',
            default => 'profile.edit',
        };
    }

    private function attributeEnabledByDefault(string $attribute): bool
    {
        $value = $this->getAttribute($attribute);

        return $value === null ? true : (bool) $value;
    }
}
