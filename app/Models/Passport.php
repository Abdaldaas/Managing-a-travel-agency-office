<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passport extends Model
{
    protected $fillable = [
        'user_id',
        'passport_number',
        'first_name',
        'last_name',
        'father_name',
        'mother_name',
        'date_of_birth',
        'place_of_birth',
        'nationality',
        'national_number',
        'gender',
        'passport_type',
        'num_dependents',
        'identity_front',
        'identity_back',
        'has_old_passport'
    ];

    protected $casts = [
        'passport_number' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'father_name' => 'string',
        'mother_name' => 'string',
        'date_of_birth' => 'date',
        'place_of_birth' => 'string',
        'nationality' => 'string',
        'national_number' => 'string',
        'gender' => 'string',
        'passport_type' => 'string',
        'num_dependents' => 'integer',
        'dependent_details' => 'array',
        'identity_proof' => 'string',
        'has_old_passport' => 'boolean'
    ];

    /**
     * Get the user that owns the passport.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}