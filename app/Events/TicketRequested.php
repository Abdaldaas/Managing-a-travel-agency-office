<?php

namespace App\Events;

use App\Models\TicketRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticketRequest;

    public function __construct(TicketRequest $ticketRequest)
    {
        $this->ticketRequest = $ticketRequest;
    }
} 