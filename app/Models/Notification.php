<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'read_at',
        'type'
    ];

    protected $casts = [
        'title' => 'string',
        'content' => 'string',
        'read_at' => 'datetime',
        'type' => 'string'
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}