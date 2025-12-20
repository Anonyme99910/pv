<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Panel extends Model
{
    use HasFactory;

    protected $fillable = [
        'panel_code',
        'name',
        'location_lat',
        'location_lng',
        'zone',
        'installation_date',
        'status',
        'efficiency',
        'temperature',
        'voltage',
        'current',
        'power_output',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'location_lat' => 'decimal:8',
        'location_lng' => 'decimal:8',
        'efficiency' => 'decimal:2',
        'temperature' => 'decimal:2',
        'voltage' => 'decimal:4',
        'current' => 'decimal:4',
        'power_output' => 'decimal:4',
    ];

    public function sensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function faults(): HasMany
    {
        return $this->hasMany(Fault::class);
    }

    public function maintenanceTasks(): HasMany
    {
        return $this->hasMany(MaintenanceTask::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function aiPredictions(): HasMany
    {
        return $this->hasMany(AiPrediction::class);
    }

    public function latestReading()
    {
        return $this->hasOne(SensorReading::class)->latestOfMany('recorded_at');
    }

    public function activeFaults(): HasMany
    {
        return $this->hasMany(Fault::class)->whereIn('status', ['active', 'critical', 'investigating']);
    }
}
