<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fault extends Model
{
    use HasFactory;

    protected $fillable = [
        'panel_id',
        'fault_type',
        'confidence',
        'severity',
        'status',
        'ai_analysis',
        'sensor_data_snapshot',
        'suggested_actions',
        'detected_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'ai_analysis' => 'array',
        'sensor_data_snapshot' => 'array',
        'suggested_actions' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function maintenanceTasks(): HasMany
    {
        return $this->hasMany(MaintenanceTask::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'critical', 'investigating']);
    }

    public function scopeCritical($query)
    {
        return $query->where('status', 'critical');
    }
}
