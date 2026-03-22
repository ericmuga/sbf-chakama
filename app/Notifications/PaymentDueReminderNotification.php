<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentDueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public float $outstandingAmount, public ?Carbon $dueDate = null) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueDateStr = $this->dueDate?->format('d M Y') ?? 'soon';

        return (new MailMessage)
            ->subject('SBF subscription payment reminder')
            ->line('This is a reminder that your SBF subscription payment is due.')
            ->line('Outstanding Amount: KES '.number_format($this->outstandingAmount, 2))
            ->line("Due Date: {$dueDateStr}")
            ->action('Make Payment', url('/portal/my-payments'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'outstanding_amount' => $this->outstandingAmount,
            'due_date' => $this->dueDate?->toDateString(),
            'message' => 'SBF subscription payment of KES '.number_format($this->outstandingAmount, 2).' is due',
        ];
    }
}
