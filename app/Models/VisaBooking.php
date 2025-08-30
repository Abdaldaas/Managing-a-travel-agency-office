<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaBooking extends Model
{
   
    protected $fillable = [
        'user_id',
        'user_name',
        'PhotoFile',
        'PassportFile',
        'status'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}