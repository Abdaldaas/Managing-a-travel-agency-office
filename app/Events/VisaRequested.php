<?php

namespace App\Events;

use App\Models\VisaBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisaRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $visaBooking;

    public function __construct(VisaBooking $visaBooking)
    {
        $this->visaBooking = $visaBooking;
    }
} 