<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRequest extends Model
{
    protected $fillable = [
        'user_id',
        'flight_id',
        'number_of_passengers',
        'total_price',
        'status',
    ];

    protected $casts = [
        'number_of_passengers' => 'integer',
        'total_price' => 'decimal:2',
        'status' => 'string'
    ];

    /**
     * Get the user that owns the ticket request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the flight associated with the ticket request.
     */
    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    /**
     * Get the admin that processed the ticket request.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id')->where('role', 'admin');
    }

    /**
     * Get the rejection reason for the ticket request.
     */
    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class);
    }
}