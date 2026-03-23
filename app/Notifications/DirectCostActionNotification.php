<?php

namespace App\Notifications;

use App\Models\ProjectDirectCost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DirectCostActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProjectDirectCost $cost,
        public string $action,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Direct cost {$this->cost->no} {$this->action}")
            ->line("Direct cost **{$this->cost->no}** has been **{$this->action}**.")
            ->line('Project: '.$this->cost->project?->no.' — '.$this->cost->project?->name)
            ->line('Description: '.$this->cost->description)
            ->line('Amount: KES '.number_format((float) $this->cost->amount, 2))
            ->action('View Project', url("/admin/projects/{$this->cost->project_id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'cost_id' => $this->cost->id,
            'cost_no' => $this->cost->no,
            'project_id' => $this->cost->project_id,
            'action' => $this->action,
            'message' => "Direct cost {$this->cost->no} has been {$this->action}",
        ];
    }
}
