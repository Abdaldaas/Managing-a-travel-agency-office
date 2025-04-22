<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = [
        'user_id',
        'rating',
        'review',
        'rateable_id',
        'rateable_type'
    ];

    protected $casts = [
        'rating' => 'integer',
        'review' => 'string',
        'rateable_id' => 'integer'
    ];

    /**
     * Get the user that created the rating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent rateable model.
     */
    public function rateable()
    {
        return $this->morphTo();
    }
}