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
        return $this->hasMany(Haj::class);
    }

   
    public function passports(): HasMany
    {
        return $this->hasMany(Passport::class);
    }
}
