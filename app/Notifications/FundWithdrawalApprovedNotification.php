<?php

namespace App\Notifications;

use App\Models\FundWithdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FundWithdrawalApprovedNotification extends Notification implements ShouldQueue
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
            ->subject("Fund withdrawal {$this->withdrawal->no} approved")
            ->line('Your fund withdrawal request has been approved.')
            ->line("Withdrawal No: {$this->withdrawal->no}")
            ->line('Amount: KES '.number_format($this->withdrawal->amount, 2))
            ->action('View Withdrawal', url("/admin/fund-withdrawals/{$this->withdrawal->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'withdrawal_id' => $this->withdrawal->id,
            'withdrawal_no' => $this->withdrawal->no,
            'message' => "Fund withdrawal {$this->withdrawal->no} has been approved",
        ];
    }
}
