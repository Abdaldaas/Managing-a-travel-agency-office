<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Visa extends Model
{
    protected $table = 'visa';

    protected $fillable = [
        'user_id',
        'country_id',
        'visa_type',
        'Total_cost',
        'Status',
        'Admin_id',
        'PassportFile',
        'PhotoFile',
        'rejection_reason_id'
    ];

    protected $casts = [
        'Total_cost' => 'decimal:2',
        'Status' => 'string'
    ];

    /**
     * Get the user that owns the visa.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the country that owns the visa.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the admin that processed the visa.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'Admin_id');
    }

    /**
     * Get the rejection reason for the visa.
     */
    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class);
    }

    /**
     * Get all comments for the visa.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get all ratings for the visa.
     */
    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }
}