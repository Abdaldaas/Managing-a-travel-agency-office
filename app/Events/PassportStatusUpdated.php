<?php

namespace App\Events;

use App\Models\PassportRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PassportStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $passportRequest;
    public $oldStatus;
    public $newStatus;

    public function __construct(PassportRequest $passportRequest, $oldStatus, $newStatus)
    {
        $this->passportRequest = $passportRequest;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
} 