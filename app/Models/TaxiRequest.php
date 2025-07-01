<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiRequest extends Model
{
    protected $fillable = [
        'user_id',
        'driver_id',
        'booking_id',
        'booking_type',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_address',
        'destination_latitude',
        'destination_longitude',
        'destination_address',
        'distance_km',
        'price',
        'status',
        'scheduled_at',
        'completed_at'
    ];

    protected $casts = [
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'destination_latitude' => 'decimal:8',
        'destination_longitude' => 'decimal:8',
        'distance_km' => 'decimal:2',
        'price' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the user that owns the taxi request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the driver assigned to the request.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class, 'driver_id');
    }

    /**
     * Get the booking associated with this request.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Calculate the price based on distance
     */
    public function calculatePrice()
    {
        // Base fare
        $baseFare = 5.00;
        
        // Price per kilometer
        $pricePerKm = 2.50;
        
        // Calculate total price
        $this->price = $baseFare + ($this->distance_km * $pricePerKm);
        
        return $this->price;
    }
} 