<?php

namespace App\Notifications;

use App\Models\ShareSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShareInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ShareSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invoice for Share Subscription {$this->subscription->no}")
            ->line('An invoice has been generated for your share subscription.')
            ->line("Subscription No: {$this->subscription->no}")
            ->line("Shares: {$this->subscription->number_of_shares}")
            ->line('Total Amount: KES '.number_format($this->subscription->total_amount, 2))
            ->action('View Subscription', url("/admin/share-subscriptions/{$this->subscription->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'subscription_no' => $this->subscription->no,
            'message' => "Invoice generated for share subscription {$this->subscription->no}",
        ];
    }
}
