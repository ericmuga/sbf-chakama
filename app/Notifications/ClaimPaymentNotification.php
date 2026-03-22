<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Claim $claim) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $method = $this->claim->payment_method?->label() ?? 'Bank Transfer';

        return (new MailMessage)
            ->subject("Payment for claim {$this->claim->no} is being processed")
            ->line("Payment for your claim {$this->claim->no} is now being processed.")
            ->line('Amount: KES '.number_format($this->claim->approved_amount ?? $this->claim->claimed_amount, 2))
            ->line("Payment Method: {$method}")
            ->line('Please allow 3–5 business days for the payment to reflect.')
            ->action('View Claim', url("/portal/my-claims/{$this->claim->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'claim_id' => $this->claim->id,
            'claim_no' => $this->claim->no,
            'message' => "Payment for claim {$this->claim->no} is being processed",
        ];
    }
}
