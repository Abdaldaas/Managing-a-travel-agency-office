<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'passport_id',
        'status',
        'passport_type',
        'price',
        'is_paid'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_paid' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function passport(): BelongsTo
    {
        return $this->belongsTo(Passport::class);
    }

    public function calculatePrice(): void
    {
        $this->price = $this->passport_type === 'regular' ? 50.00 : 170.00;
    }
}