<?php

namespace App\Notifications;

use App\Models\Finance\CashReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CashReceipt $receipt) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment of KES '.number_format($this->receipt->amount, 2).' received')
            ->line('We have received your payment. Thank you!')
            ->line("Receipt No: {$this->receipt->no}")
            ->line("Date: {$this->receipt->posting_date?->format('d M Y')}")
            ->line('Amount: KES '.number_format($this->receipt->amount, 2))
            ->action('View Statement', url('/portal/my-statement'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'receipt_id' => $this->receipt->id,
            'receipt_no' => $this->receipt->no,
            'message' => 'Payment of KES '.number_format($this->receipt->amount, 2).' received',
        ];
    }
}
