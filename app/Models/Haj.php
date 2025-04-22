<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Haj extends Model
{
    protected $fillable = [
        'user_id',
        'package_type',
        'status',
        'departure_date',
        'return_date',
        'total_cost',
        'payment_status'
    ];

    protected $casts = [
        'package_type' => 'string',
        'status' => 'string',
        'departure_date' => 'date',
        'return_date' => 'date',
        'total_cost' => 'decimal:2',
        'payment_status' => 'string'
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