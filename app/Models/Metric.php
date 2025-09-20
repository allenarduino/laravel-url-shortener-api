<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    protected $fillable = [
        'metric_name',
        'metric_type',
        'labels',
        'value',
        'recorded_at',
    ];

    protected $casts = [
        'labels' => 'array',
        'recorded_at' => 'datetime',
        'value' => 'decimal:4',
    ];

    /**
     * Record a counter metric
     */
    public static function counter(string $name, float $value = 1, array $labels = []): void
    {
        self::create([
            'metric_name' => $name,
            'metric_type' => 'counter',
            'labels' => $labels,
            'value' => $value,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Record a gauge metric
     */
    public static function gauge(string $name, float $value, array $labels = []): void
    {
        self::create([
            'metric_name' => $name,
            'metric_type' => 'gauge',
            'labels' => $labels,
            'value' => $value,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Record a histogram metric (for response times, etc.)
     */
    public static function histogram(string $name, float $value, array $labels = []): void
    {
        self::create([
            'metric_name' => $name,
            'metric_type' => 'histogram',
            'labels' => $labels,
            'value' => $value,
            'recorded_at' => now(),
        ]);
    }
}
