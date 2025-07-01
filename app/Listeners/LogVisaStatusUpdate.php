<?php

namespace App\Listeners;

use App\Events\VisaStatusUpdated;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class LogVisaStatusUpdate
{
    public function handle(VisaStatusUpdated $event)
    {
        // Log to file
        Log::info('Visa request status updated', [
            'user_id' => $event->visaBooking->user_id,
            'user_name' => $event->visaBooking->user_name,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'updated_at' => now()
        ]);

        // Store in database
        ActivityLog::create([
            'user_id' => $event->visaBooking->user_id,
            'event_type' => 'visa_status_updated',
            'request_type' => 'visa',
            'request_id' => $event->visaBooking->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'additional_data' => [
                'user_name' => $event->visaBooking->user_name
            ]
        ]);
    }
} 