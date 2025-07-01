<?php

namespace App\Listeners;

use App\Events\PassportStatusUpdated;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class LogPassportStatusUpdate
{
    public function handle(PassportStatusUpdated $event)
    {
        // Log to file
        Log::info('Passport request status updated', [
            'user_id' => $event->passportRequest->user_id,
            'passport_type' => $event->passportRequest->passport_type,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'updated_at' => now()
        ]);

        // Store in database
        ActivityLog::create([
            'user_id' => $event->passportRequest->user_id,
            'event_type' => 'passport_status_updated',
            'request_type' => 'passport',
            'request_id' => $event->passportRequest->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'additional_data' => [
                'passport_type' => $event->passportRequest->passport_type,
                'price' => $event->passportRequest->price
            ]
        ]);
    }
} 