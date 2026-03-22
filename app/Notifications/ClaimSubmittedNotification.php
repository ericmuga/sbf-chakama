<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimSubmittedNotification extends Notification implements ShouldQueue
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
            ->subject("New claim {$this->claim->no} submitted by {$this->claim->member?->name}")
            ->line("A new {$this->claim->claim_type->label()} claim has been submitted.")
            ->line("Claim No: {$this->claim->no}")
            ->line("Claimant: {$this->claim->member?->name}")
            ->line('Amount: KES '.number_format($this->claim->claimed_amount, 2))
            ->action('Review Claim', url("/admin/claims/{$this->claim->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'claim_id' => $this->claim->id,
            'claim_no' => $this->claim->no,
            'message' => "New claim {$this->claim->no} submitted by {$this->claim->member?->name}",
        ];
    }
}
