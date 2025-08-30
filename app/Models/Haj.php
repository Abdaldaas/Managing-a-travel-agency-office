<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Haj extends Model
{
    protected $table = 'haj';
    protected $fillable = [
        'category_of',
        'package_type',
        'total_price',
        'departure_date',
        'return_date',
        'takeoff_time',
        'landing_time'
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'total_price' => 'decimal:2',
        'takeoff_time' => 'datetime',
        'landing_time' => 'datetime'
    ];

    /**
     * Get the user that owns the Haj request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all comments for the Haj request.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get all ratings for the Haj request.
     */
    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }
}