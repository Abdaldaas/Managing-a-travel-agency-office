<?php

namespace App\Notifications;

use App\Models\Haj;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HajStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $haj;

    public function __construct(Haj $haj)
    {
        $this->haj = $haj;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $status = $this->haj->status;
        $subject = 'Haj Request ' . ucfirst($status);
        $message = 'Your Haj request has been ' . $status;

        if ($status === 'rejected' && $this->haj->rejection_reason) {
            $message .= ". Reason: {$this->haj->rejection_reason}";
        }

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable)
    {
        return [
            'haj_id' => $this->haj->id,
            'status' => $this->haj->status,
            'rejection_reason' => $this->haj->rejection_reason,
            'admin_notes' => $this->haj->admin_notes
        ];
    }
}