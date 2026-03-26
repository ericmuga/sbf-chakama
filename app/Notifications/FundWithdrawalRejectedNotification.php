<?php

namespace App\Notifications;

use App\Models\FundWithdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FundWithdrawalRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FundWithdrawal $withdrawal) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Fund withdrawal {$this->withdrawal->no} rejected")
            ->line('Your fund withdrawal request has been rejected.')
            ->line("Withdrawal No: {$this->withdrawal->no}")
            ->line('Amount: KES '.number_format($this->withdrawal->amount, 2))
            ->line("Reason: {$this->withdrawal->rejection_reason}")
            ->action('View Withdrawal', url("/admin/fund-withdrawals/{$this->withdrawal->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'withdrawal_id' => $this->withdrawal->id,
            'withdrawal_no' => $this->withdrawal->no,
            'reason' => $this->withdrawal->rejection_reason,
            'message' => "Fund withdrawal {$this->withdrawal->no} has been rejected",
        ];
    }
}
