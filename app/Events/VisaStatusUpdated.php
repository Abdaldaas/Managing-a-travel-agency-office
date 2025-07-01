<?php

namespace App\Events;

use App\Models\VisaBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisaStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $visaBooking;
    public $oldStatus;
    public $newStatus;

    public function __construct(VisaBooking $visaBooking, $oldStatus, $newStatus)
    {
        $this->visaBooking = $visaBooking;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
} 