<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'age'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'age' => 'integer',
    ];

    /**
     * Get the visa applications for the user.
     */
    public function visas(): HasMany
    {
        return $this->hasMany(Visa::class);
    }

    /**
     * Get the ratings given by the user.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get the comments written by the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the ticket requests for the user.
     */
    public function ticketRequests(): HasMany
    {
        return $this->hasMany(TicketRequest::class);
    }

    /**
     * Get the haj applications for the user.
     */
    public function hajApplications(): HasMany
    {
        return $this->hasMany(Haj::class);
    }

    /**
     * Get the passports for the user.
     */
    public function passports(): HasMany
    {
        return $this->hasMany(Passport::class);
    }
}
