<?php

namespace App\Notifications;

use App\Models\TicketRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketRequestStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticketRequest;

    public function __construct(TicketRequest $ticketRequest)
    {
        $this->ticketRequest = $ticketRequest;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $status = $this->ticketRequest->status;
        $subject = 'Ticket Request ' . ucfirst($status);
        $message = 'Your ticket request has been ' . $status;

        if ($status === 'rejected' && $this->ticketRequest->rejection_reason) {
            $message .= ". Reason: {$this->ticketRequest->rejection_reason}";
        } elseif ($status === 'approved') {
            $message .= ". Flight details: {$this->ticketRequest->flight->flight_number} from {$this->ticketRequest->flight->departure_city} to {$this->ticketRequest->flight->arrival_city}";
        }

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_request_id' => $this->ticketRequest->id,
            'status' => $this->ticketRequest->status,
            'rejection_reason' => $this->ticketRequest->rejection_reason,
            'admin_notes' => $this->ticketRequest->admin_notes,
            'flight_number' => $this->ticketRequest->flight->flight_number
        ];
    }
}