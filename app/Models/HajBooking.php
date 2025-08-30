<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HajBooking extends Model
{
    protected $fillable = [
        'user_id',
        'haj_id',
        'status',
        'passport_file',
        'photo_file',
        'health_report_file',
        'vaccination_certificate'
    ];

    protected $casts = [
        'status' => 'string',
        'rejection_reason' => 'string'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function haj(): BelongsTo
    {
        return $this->belongsTo(Haj::class);
    }
    public function category(): HasOne
    {
        return $this->hasOne(Category::class);
    }
}