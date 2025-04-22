<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'role' => 'string',
    ];

    /**
     * Get the visa applications processed by the admin.
     */
    public function visas(): HasMany
    {
        return $this->hasMany(Visa::class, 'Admin_id');
    }

    /**
     * Get the ticket requests processed by the admin.
     */
    public function ticketRequests(): HasMany
    {
        return $this->hasMany(TicketRequest::class, 'Admin_id');
    }

    /**
     * Get the haj applications processed by the admin.
     */
    public function hajApplications(): HasMany
    {
        return $this->hasMany(Haj::class, 'Admin_id');
    }
}