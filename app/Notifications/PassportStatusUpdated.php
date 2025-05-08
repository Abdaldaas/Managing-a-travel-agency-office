<?php

namespace App\Notifications;

use App\Models\Passport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PassportStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $passport;

    public function __construct(Passport $passport)
    {
        $this->passport = $passport;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $status = $this->passport->status;
        $subject = 'Passport Request ' . ucfirst($status);
        $message = 'Your passport request has been ' . $status;

        if ($status === 'rejected' && $this->passport->rejection_reason) {
            $message .= ". Reason: {$this->passport->rejection_reason}";
        }

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable)
    {
        return [
            'passport_id' => $this->passport->id,
            'status' => $this->passport->status,
            'rejection_reason' => $this->passport->rejection_reason,
            'admin_notes' => $this->passport->admin_notes
        ];
    }
}