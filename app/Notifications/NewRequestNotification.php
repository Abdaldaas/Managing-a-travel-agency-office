<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $requestData;
    private $requestType;

    public function __construct($requestData, $requestType)
    {
        $this->requestData = $requestData;
        $this->requestType = $requestType;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => $this->requestType,
            'message' => "New {$this->requestType} request received",
            'request_id' => $this->requestData->id,
            'user_name' => $this->requestData->user_name ?? null,
            'status' => $this->requestData->status,
            'created_at' => now()
        ];
    }
} 