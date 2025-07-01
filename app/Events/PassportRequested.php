<?php

namespace App\Events;

use App\Models\PassportRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PassportRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $passportRequest;

    public function __construct(PassportRequest $passportRequest)
    {
        $this->passportRequest = $passportRequest;
    }
} 