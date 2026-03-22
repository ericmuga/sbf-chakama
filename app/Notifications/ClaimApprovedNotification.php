<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimApprovedNotification extends Notification implements ShouldQueue
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
            ->subject("Your claim {$this->claim->no} has been approved")
            ->line("Great news! Your claim {$this->claim->no} has been fully approved.")
            ->line('Approved Amount: KES '.number_format($this->claim->approved_amount ?? $this->claim->claimed_amount, 2))
            ->line('Payment will be processed shortly via your preferred payment method.')
            ->action('View Claim', url("/portal/my-claims/{$this->claim->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'claim_id' => $this->claim->id,
            'claim_no' => $this->claim->no,
            'message' => "Your claim {$this->claim->no} has been approved",
        ];
    }
}
