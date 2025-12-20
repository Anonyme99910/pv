<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'panel_id',
        'irradiance',
        'temperature',
        'voltage',
        'current',
        'power_output',
        'dust_level',
        'humidity',
        'season',
        'inverter_id',
        'bus_voltage_pu',
        'load_demand_mw',
        'control_scheme',
        'reactive_power_mvar',
        'voltage_setting_pu',
        'vocr',
        'power_loss_kw',
        'recorded_at',
    ];

    protected $casts = [
        'irradiance' => 'decimal:2',
        'temperature' => 'decimal:2',
        'voltage' => 'decimal:4',
        'current' => 'decimal:4',
        'power_output' => 'decimal:4',
        'dust_level' => 'decimal:2',
        'humidity' => 'decimal:2',
        'bus_voltage_pu' => 'decimal:4',
        'load_demand_mw' => 'decimal:4',
        'reactive_power_mvar' => 'decimal:4',
        'voltage_setting_pu' => 'decimal:4',
        'vocr' => 'decimal:4',
        'power_loss_kw' => 'decimal:4',
        'recorded_at' => 'datetime',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }
}
