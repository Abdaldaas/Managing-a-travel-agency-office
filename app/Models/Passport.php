<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passport extends Model
{
    protected $fillable = [
        'user_id',
        'passport_number',
        'issue_date',
        'expiry_date',
        'place_of_issue',
        'nationality'
    ];

    protected $casts = [
        'passport_number' => 'string',
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'place_of_issue' => 'string',
        'nationality' => 'string'
    ];

    /**
     * Get the user that owns the passport.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}