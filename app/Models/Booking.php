<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'type',
        'status',
        'price',
        'bookable_id',
        'bookable_type',
        'stripe_payment_intent_id'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookable()
    {
        return $this->morphTo();
    }

    public function visa()
    {
        return $this->belongsTo(Visa::class, 'bookable_id')->where('bookable_type', Visa::class);
    }

    public function ticketRequest()
    {
        return $this->belongsTo(TicketRequest::class, 'bookable_id')->where('bookable_type', TicketRequest::class);
    }

    public function haj()
    {
        return $this->belongsTo(Haj::class, 'bookable_id')->where('bookable_type', Haj::class);
    }

   
}