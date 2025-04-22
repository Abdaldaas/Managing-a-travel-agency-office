<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'ticket_request_id',
        'amount',
        'payment_method',
        'status',
        'transaction_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_method' => 'string',
        'status' => 'string',
        'transaction_id' => 'string'
    ];

    /**
     * Get the ticket request associated with the payment.
     */
    public function ticketRequest(): BelongsTo
    {
        return $this->belongsTo(TicketRequest::class);
    }
}