<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'panel_id',
        'fault_id',
        'assigned_to',
        'created_by',
        'task_type',
        'description',
        'status',
        'priority',
        'scheduled_date',
        'scheduled_time',
        'estimated_duration',
        'actual_duration',
        'started_at',
        'completed_at',
        'completion_notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function fault(): BelongsTo
    {
        return $this->belongsTo(Fault::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', now()->toDateString())
                     ->where('status', 'scheduled');
    }
}
