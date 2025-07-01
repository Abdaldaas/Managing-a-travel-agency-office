<?php

namespace App\Listeners;

use App\Events\TicketRequested;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class LogTicketRequest
{
    public function handle(TicketRequested $event)
    {
        // Log to file
        Log::info('New ticket request created', [
            'user_id' => $event->ticketRequest->user_id,
            'flight_id' => $event->ticketRequest->flight_id,
            'status' => $event->ticketRequest->status,
            'total_price' => $event->ticketRequest->total_price,
            'created_at' => now()
        ]);

        // Store in database
        ActivityLog::create([
            'user_id' => $event->ticketRequest->user_id,
            'event_type' => 'ticket_requested',
            'request_type' => 'ticket',
            'request_id' => $event->ticketRequest->id,
            'new_status' => $event->ticketRequest->status,
            'additional_data' => [
                'flight_id' => $event->ticketRequest->flight_id,
                'total_price' => $event->ticketRequest->total_price
            ]
        ]);
    }
} 