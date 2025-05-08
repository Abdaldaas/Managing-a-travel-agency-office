<?php

namespace App\Notifications;

use App\Models\Visa;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisaStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $visa;

    public function __construct(Visa $visa)
    {
        $this->visa = $visa;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $status = $this->visa->status;
        $subject = 'Visa Request ' . ucfirst($status);
        $message = 'Your visa request has been ' . $status;

        if ($status === 'rejected' && $this->visa->rejection_reason) {
            $message .= ". Reason: {$this->visa->rejection_reason}";
        }

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable)
    {
        return [
            'visa_id' => $this->visa->id,
            'status' => $this->visa->status,
            'rejection_reason' => $this->visa->rejection_reason,
            'admin_notes' => $this->visa->admin_notes
        ];
    }
}