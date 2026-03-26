<?php

namespace App\Notifications;

use App\Models\Finance\CashReceipt;
use App\Models\ShareSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SharePaymentConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ShareSubscription $subscription,
        public CashReceipt $receipt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment received for {$this->subscription->no}")
            ->line("We have received your payment for share subscription {$this->subscription->no}.")
            ->line('Amount Paid: KES '.number_format($this->receipt->amount, 2))
            ->line('Outstanding Balance: KES '.number_format($this->subscription->amount_outstanding, 2))
            ->action('View Subscription', url("/admin/share-subscriptions/{$this->subscription->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'subscription_no' => $this->subscription->no,
            'amount_paid' => $this->receipt->amount,
            'message' => 'Payment of KES '.number_format($this->receipt->amount, 2)." received for {$this->subscription->no}",
        ];
    }
}
