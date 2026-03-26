<?php

namespace App\Notifications;

use App\Models\FundWithdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FundWithdrawalApprovalRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public FundWithdrawal $withdrawal,
        public int $step,
        public int $totalSteps,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Fund withdrawal {$this->withdrawal->no} requires your approval (Step {$this->step}/{$this->totalSteps})")
            ->line('A fund withdrawal requires your approval.')
            ->line("Withdrawal No: {$this->withdrawal->no}")
            ->line('Amount: KES '.number_format($this->withdrawal->amount, 2))
            ->line("Approval Step: {$this->step} of {$this->totalSteps}")
            ->action('Review Withdrawal', url("/admin/fund-withdrawals/{$this->withdrawal->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'withdrawal_id' => $this->withdrawal->id,
            'withdrawal_no' => $this->withdrawal->no,
            'step' => $this->step,
            'total_steps' => $this->totalSteps,
            'message' => "Fund withdrawal {$this->withdrawal->no} requires your approval (Step {$this->step}/{$this->totalSteps})",
        ];
    }
}
