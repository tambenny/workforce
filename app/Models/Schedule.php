<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_form_id',
        'user_id',
        'location_id',
        'shift_date',
        'starts_at',
        'ends_at',
        'status',
        'change_type',
        'change_reason',
        'changed_by',
        'changed_at',
        'reapproval_cycle',
        'notes',
        'created_by',
        'approved_by',
        'rejected_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'changed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(ScheduleForm::class, 'schedule_form_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function punches(): HasMany
    {
        return $this->hasMany(TimePunch::class);
    }
}
