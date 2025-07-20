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


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

   
    public function taxiRequests(): HasMany
    {
        return $this->hasMany(TaxiRequest::class, 'driver_id');
    }

   
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

        
        $earthRadius = 6371;

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

 
    public function completedTrips(): HasMany
    {
        return $this->hasMany(TaxiRequest::class, 'driver_id')
                    ->where('status', 'completed');
    }

   
    public function trips(): HasMany
    {
        return $this->hasMany(TaxiRequest::class, 'driver_id');
    }

   
    public function ratings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Rating::class,
            TaxiRequest::class,
            'driver_id', 
            'rateable_id', 
            'id',
            'id' 
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