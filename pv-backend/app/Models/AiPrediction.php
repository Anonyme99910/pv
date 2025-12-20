<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'panel_id',
        'model_type',
        'model_version',
        'input_data',
        'prediction',
        'confidence',
        'processing_time_ms',
        'status',
        'error_message',
    ];

    protected $casts = [
        'input_data' => 'array',
        'prediction' => 'array',
        'confidence' => 'decimal:2',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
