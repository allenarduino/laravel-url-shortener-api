<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Url extends Model
{
    protected $fillable = [
        'original_url',
        'short_code',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->short_code)) {
                $model->short_code = self::generateUniqueShortCode();
            }
        });
    }

    public static function generateUniqueShortCode($length = 6)
    {
        do {
            // You can tune length or use a better generator if needed
            $code = Str::random($length);
        } while (self::where('short_code', $code)->exists());

        return $code;
    }
}
