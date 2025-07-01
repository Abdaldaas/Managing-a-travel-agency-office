<?php

namespace App\Listeners;

use App\Events\VisaRequested;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class LogVisaRequest
{
    public function handle(VisaRequested $event)
    {
        // Log to file
        Log::info('New visa request created', [
            'user_id' => $event->visaBooking->user_id,
            'user_name' => $event->visaBooking->user_name,
            'status' => $event->visaBooking->status,
            'created_at' => now()
        ]);

        // Store in database
        ActivityLog::create([
            'user_id' => $event->visaBooking->user_id,
            'event_type' => 'visa_requested',
            'request_type' => 'visa',
            'request_id' => $event->visaBooking->id,
            'new_status' => $event->visaBooking->status,
            'additional_data' => [
                'user_name' => $event->visaBooking->user_name
            ]
        ]);
    }
} 