<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'request_type',
        'request_id',
        'old_status',
        'new_status',
        'additional_data'
    ];

    protected $casts = [
        'additional_data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 