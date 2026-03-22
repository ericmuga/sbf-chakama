<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimApprovalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Claim $claim, public int $stepOrder, public int $totalSteps) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Action required: Claim {$this->claim->no} awaits your approval")
            ->line("Claim {$this->claim->no} requires your approval (Step {$this->stepOrder} of {$this->totalSteps}).")
            ->line("Claimant: {$this->claim->member?->name}")
            ->line("Subject: {$this->claim->subject}")
            ->line('Amount: KES '.number_format($this->claim->claimed_amount, 2))
            ->action('Review Claim', url("/admin/claims/{$this->claim->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'claim_id' => $this->claim->id,
            'claim_no' => $this->claim->no,
            'message' => "Action required: Claim {$this->claim->no} awaits your approval (Step {$this->stepOrder} of {$this->totalSteps})",
        ];
    }
}
