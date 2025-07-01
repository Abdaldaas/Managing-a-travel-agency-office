<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\NewRequestNotification;
use Illuminate\Support\Facades\Notification;

class SendNewRequestNotification
{
    public function handle($event)
    {
        // Get all admin users
        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();

        // Get request type and data based on event class
        $requestType = $this->getRequestType($event);
        $requestData = $this->getRequestData($event);

        // Notify all admins
        Notification::send($admins, new NewRequestNotification($requestData, $requestType));
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