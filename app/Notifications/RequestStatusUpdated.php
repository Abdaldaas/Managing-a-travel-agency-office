<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class RequestStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    private $requestData;
    private $requestType;
    private $oldStatus;
    private $newStatus;

    public function __construct($requestData, $requestType, $oldStatus, $newStatus)
    {
        $this->requestData = $requestData;
        $this->requestType = $requestType;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => $this->requestType,
            'message' => "Your {$this->requestType} request status has been updated to {$this->newStatus}",
            'request_id' => $this->requestData->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'updated_at' => now()
        ];
    }
} 