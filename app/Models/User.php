<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property-read Collection|DatabaseNotification[] $notifications
 * @property-read Collection|DatabaseNotification[] $readNotifications
 * @property-read Collection|DatabaseNotification[] $unreadNotifications
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'age',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'age' => 'integer',
    ];

    /**
     * Get the visa applications for the user.
     */
    public function visas(): HasMany
    {
        return $this->hasMany(Visa::class);
    }
    public function visasrequests(): HasMany
    {
        return $this->hasMany(VisaBooking::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ticketRequests(): HasMany
    {
        return $this->hasMany(TicketRequest::class);
    }

    public function hajRequests()
    {
        return $this->hasMany(HajBooking::class);
    }

    public function passports(): HasMany
    {
        return $this->hasMany(Passport::class);
    }
    public function passportrequests(): HasMany
    {
        return $this->hasMany(PassportRequest::class);
    }
    /**
     * Get the taxi driver profile associated with the user.
     */
    public function taxiDriver(): HasOne
    {
        return $this->hasOne(TaxiDriver::class);
    }

    /**
     * Get the taxi requests made by the user.
     */
    public function taxiRequests(): HasMany
    {
        return $this->hasMany(TaxiRequest::class);
    }

    /**
     * Check if the user is a taxi driver
     */
    public function isTaxiDriver(): bool
    {
        return $this->role === 'taxi_driver';
    }
}
