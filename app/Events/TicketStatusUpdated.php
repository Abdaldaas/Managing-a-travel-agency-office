<?php

namespace App\Events;

use App\Models\TicketRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticketRequest;
    public $oldStatus;
    public $newStatus;

    public function __construct(TicketRequest $ticketRequest, $oldStatus, $newStatus)
    {
        $this->ticketRequest = $ticketRequest;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
} 