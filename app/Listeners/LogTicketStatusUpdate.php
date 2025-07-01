<?php

namespace App\Listeners;

use App\Events\TicketStatusUpdated;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class LogTicketStatusUpdate
{
    public function handle(TicketStatusUpdated $event)
    {
        // Log to file
        Log::info('Ticket request status updated', [
            'user_id' => $event->ticketRequest->user_id,
            'flight_id' => $event->ticketRequest->flight_id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'updated_at' => now()
        ]);

        // Store in database
        ActivityLog::create([
            'user_id' => $event->ticketRequest->user_id,
            'event_type' => 'ticket_status_updated',
            'request_type' => 'ticket',
            'request_id' => $event->ticketRequest->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'additional_data' => [
                'flight_id' => $event->ticketRequest->flight_id,
                'total_price' => $event->ticketRequest->total_price
            ]
        ]);
    }
} 