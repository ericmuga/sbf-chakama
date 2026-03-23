<?php

namespace App\Notifications;

use App\Models\ShareSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SharePaymentOverdueNotification extends Notification implements ShouldQueue
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
            ->subject("Outstanding balance reminder: {$this->subscription->no}")
            ->line('You have an outstanding balance on your share subscription.')
            ->line("Subscription No: {$this->subscription->no}")
            ->line('Outstanding Balance: KES '.number_format($this->subscription->amount_outstanding, 2))
            ->action('Make Payment', url("/admin/share-subscriptions/{$this->subscription->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'subscription_no' => $this->subscription->no,
            'outstanding' => $this->subscription->amount_outstanding,
            'message' => "Outstanding balance reminder for share subscription {$this->subscription->no}",
        ];
    }
}
