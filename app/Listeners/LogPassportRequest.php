<?php

namespace App\Listeners;

use App\Events\PassportRequested;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class LogPassportRequest
{
    public function handle(PassportRequested $event)
    {
        // Log to file
        Log::info('New passport request created', [
            'user_id' => $event->passportRequest->user_id,
            'passport_type' => $event->passportRequest->passport_type,
            'status' => $event->passportRequest->status,
            'created_at' => now()
        ]);

        // Store in database
        ActivityLog::create([
            'user_id' => $event->passportRequest->user_id,
            'event_type' => 'passport_requested',
            'request_type' => 'passport',
            'request_id' => $event->passportRequest->id,
            'new_status' => $event->passportRequest->status,
            'additional_data' => [
                'passport_type' => $event->passportRequest->passport_type,
                'price' => $event->passportRequest->price
            ]
        ]);
    }
} 