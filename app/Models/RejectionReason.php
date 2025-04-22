<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RejectionReason extends Model
{
    protected $fillable = [
        'reason',
        'description'
    ];

    /**
     * Get the visa applications that use this rejection reason.
     */
    public function visas(): HasMany
    {
        return $this->hasMany(Visa::class);
    }

    /**
     * Get the ticket requests that use this rejection reason.
     */
    public function ticketRequests(): HasMany
    {
        return $this->hasMany(TicketRequest::class);
    }

    /**
     * Get the haj applications that use this rejection reason.
     */
    public function hajApplications(): HasMany
    {
        return $this->hasMany(Haj::class);
    }
}