<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemThreshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'min_value',
        'max_value',
        'unit',
        'description',
    ];

    protected $casts = [
        'min_value' => 'decimal:4',
        'max_value' => 'decimal:4',
    ];

    public static function getValue(string $key): ?array
    {
        $threshold = self::where('key', $key)->first();

        if (!$threshold) {
            return null;
        }

        return [
            'min' => $threshold->min_value,
            'max' => $threshold->max_value,
            'unit' => $threshold->unit,
        ];
    }
}
