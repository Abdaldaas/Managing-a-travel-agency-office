<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RejectionReason extends Model
{
    protected $fillable = [
        'reason',
        'request_type',
        'request_id',
        'user_id'
    ];

    /**
     * Get the user who owns this rejection reason.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}