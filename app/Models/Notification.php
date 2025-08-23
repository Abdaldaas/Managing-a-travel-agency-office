<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'notifiable_type',
        'notifiable_id',
        'read_at',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }
}
