<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Claim $claim) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your claim {$this->claim->no} was not approved")
            ->line("We regret to inform you that claim {$this->claim->no} was not approved.")
            ->line("Reason: {$this->claim->rejection_reason}")
            ->line('Please contact the office if you have any questions.')
            ->action('View Claim', url("/portal/my-claims/{$this->claim->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'claim_id' => $this->claim->id,
            'claim_no' => $this->claim->no,
            'message' => "Your claim {$this->claim->no} was not approved",
        ];
    }
}
