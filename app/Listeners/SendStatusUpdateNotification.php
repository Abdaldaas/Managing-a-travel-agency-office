<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\RequestStatusUpdated;

class SendStatusUpdateNotification
{
    public function handle($event)
    {
        // Get request type and data based on event class
        $requestType = $this->getRequestType($event);
        $requestData = $this->getRequestData($event);
        
        // Get the user who made the request
        $user = User::find($requestData->user_id);
        
        if ($user) {
            $user->notify(new RequestStatusUpdated(
                $requestData,
                $requestType,
                $event->oldStatus,
                $event->newStatus
            ));
        }
    }

    private function getRequestType($event)
    {
        $eventClass = get_class($event);
        if (strpos($eventClass, 'Passport') !== false) {
            return 'passport';
        } elseif (strpos($eventClass, 'Visa') !== false) {
            return 'visa';
        } elseif (strpos($eventClass, 'Ticket') !== false) {
            return 'ticket';
        }
        return 'unknown';
    }

    private function getRequestData($event)
    {
        if (isset($event->passportRequest)) {
            return $event->passportRequest;
        } elseif (isset($event->visaBooking)) {
            return $event->visaBooking;
        } elseif (isset($event->ticketRequest)) {
            return $event->ticketRequest;
        }
        return null;
    }
} 