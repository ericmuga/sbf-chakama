<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetThresholdNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public float $percent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $formattedPercent = number_format($this->percent, 1);

        return (new MailMessage)
            ->subject("Budget Alert: Project {$this->project->no} at {$formattedPercent}%")
            ->line("Project **{$this->project->no} — {$this->project->name}** has reached **{$formattedPercent}%** budget utilisation.")
            ->line('Budget: KES '.number_format((float) $this->project->budget, 2))
            ->line('Spent: KES '.number_format((float) $this->project->spent, 2))
            ->line('Remaining: KES '.number_format((float) $this->project->budget - (float) $this->project->spent, 2))
            ->action('View Project', url("/admin/projects/{$this->project->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_no' => $this->project->no,
            'utilisation_percent' => $this->percent,
            'message' => "Budget alert: Project {$this->project->no} at ".number_format($this->percent, 1).'%',
        ];
    }
}
