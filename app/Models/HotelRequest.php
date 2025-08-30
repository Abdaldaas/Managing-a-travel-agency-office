<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'hotel_id',
        'ticket_id',
        'price',
        'status',
        'user_id',
    ];

   
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

   
    public function ticketRequest()
    {
        return $this->belongsTo(TicketRequest::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
