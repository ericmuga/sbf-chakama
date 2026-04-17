<?php

namespace App\Notifications;

use App\Models\ShareBillingRun;
use App\Models\ShareSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShareBillingRunInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ShareBillingRun $run,
        public readonly ShareSubscription $subscription,
        public readonly float $amount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueDate = ($this->run->due_date ?? $this->run->billing_date)->format('d M Y');

        return (new MailMessage)
            ->subject("New Invoice: {$this->run->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A new share invoice has been raised on your account.')
            ->line("**Billing: {$this->run->title}**")
            ->line("Shares: {$this->subscription->number_of_shares}")
            ->line('Amount Due: KES '.number_format($this->amount, 2))
            ->line("Due Date: {$dueDate}")
            ->action('View & Pay Invoice', url('/portal/my-invoices'))
            ->line('Please log in to the member portal to view and pay your invoice.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'billing_run_id' => $this->run->id,
            'title' => $this->run->title,
            'amount' => $this->amount,
            'due_date' => ($this->run->due_date ?? $this->run->billing_date)->toDateString(),
            'message' => "New invoice: {$this->run->title} — KES ".number_format($this->amount, 2),
        ];
    }
}
