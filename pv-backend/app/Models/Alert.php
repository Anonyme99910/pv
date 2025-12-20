<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'panel_id',
        'fault_id',
        'type',
        'title',
        'message',
        'is_read',
        'is_dismissed',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function fault(): BelongsTo
    {
        return $this->belongsTo(Fault::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_dismissed', false);
    }
}
