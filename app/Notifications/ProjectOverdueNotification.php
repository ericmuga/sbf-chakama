<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Project $project) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysOverdue = (int) $this->project->due_date->diffInDays(now());

        return (new MailMessage)
            ->subject("Project {$this->project->no} is overdue")
            ->line("Project **{$this->project->no} — {$this->project->name}** is overdue by {$daysOverdue} day(s).")
            ->line('Due date: '.$this->project->due_date->toFormattedDateString())
            ->line('Status: '.$this->project->status->label())
            ->action('View Project', url("/admin/projects/{$this->project->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_no' => $this->project->no,
            'due_date' => $this->project->due_date->toDateString(),
            'message' => "Project {$this->project->no} is overdue",
        ];
    }
}
