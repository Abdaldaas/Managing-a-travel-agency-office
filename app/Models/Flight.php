<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Flight extends Model
{
    protected $fillable = [
        'flight_number',
        'airline',
        'departure_city',
        'arrival_city',
        'departure_time',
        'arrival_time',
        'price',
        'available_seats',
        'status'
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'price' => 'decimal:2',
        'available_seats' => 'integer',
        'status' => 'string'
    ];

    /**
     * Get the ticket requests for this flight.
     */
    public function ticketRequests(): HasMany
    {
        return $this->hasMany(TicketRequest::class);
    }

    /**
     * Get all comments for the flight.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get all ratings for the flight.
     */
    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }
}