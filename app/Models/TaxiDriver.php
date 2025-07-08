<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Notifications\Notifiable;
use App\Models\Rating;
use App\Models\TaxiRequest;

class TaxiDriver extends Model
{
    use Notifiable;

    protected $fillable = [
        'user_id',
        'car_model',
        'car_plate_number',
        'license_number',
        'status',
        'current_latitude',
        'current_longitude',
        'rating',
        'total_trips'
    ];

    protected $casts = [
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'rating' => 'decimal:2',
        'total_trips' => 'integer'
    ];

    /**
     * Get the user that owns the taxi driver profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the taxi requests for the driver.
     */
    public function taxiRequests(): HasMany
    {
        return $this->hasMany(TaxiRequest::class, 'driver_id');
    }

    /**
     * Get the active/current taxi request for the driver.
     */
    public function currentRequest(): BelongsTo
    {
        return $this->taxiRequests()
            ->where('status', 'in:accepted,in_progress')
            ->latest()
            ->first();
    }

    /**
     * Update driver's location
     */
    public function updateLocation($latitude, $longitude)
    {
        $this->current_latitude = $latitude;
        $this->current_longitude = $longitude;
        $this->save();
    }

    /**
     * Calculate distance to a point
     */
    public function distanceTo($latitude, $longitude)
    {
        if (!$this->current_latitude || !$this->current_longitude) {
            return null;
        }

        // Using Haversine formula
        $earthRadius = 6371; // Radius of the earth in km

        $latFrom = deg2rad($this->current_latitude);
        $lonFrom = deg2rad($this->current_longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /**
     * Get all completed trips for this driver
     */
    public function completedTrips(): HasMany
    {
        return $this->hasMany(TaxiRequest::class, 'driver_id')
                    ->where('status', 'completed');
    }

    /**
     * Get all trips for this driver
     */
    public function trips(): HasMany
    {
        return $this->hasMany(TaxiRequest::class, 'driver_id');
    }

    /**
     * Get all ratings for this driver through their completed trips
     */
    public function ratings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Rating::class,
            TaxiRequest::class,
            'driver_id', // Foreign key on taxi_requests table
            'rateable_id', // Foreign key on ratings table
            'id', // Local key on taxi_drivers table
            'id' // Local key on taxi_requests table
        )->where('rateable_type', TaxiRequest::class);
    }

    /**
     * Get the average rating for this driver
     */
    public function getAverageRatingAttribute(): float
    {
        return $this->ratings()->avg('star') ?? 0.0;
    }
} 