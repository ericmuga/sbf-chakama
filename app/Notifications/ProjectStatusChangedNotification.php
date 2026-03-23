<?php

namespace App\Notifications;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public ?ProjectStatus $fromStatus,
        public ProjectStatus $toStatus,
        public User $changedBy,
        public ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = "Project {$this->project->no} \"{$this->project->name}\" → {$this->toStatus->label()}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->line("Project **{$this->project->no}** status has changed.")
            ->line('From: '.($this->fromStatus?->label() ?? 'N/A').' → **'.$this->toStatus->label().'**')
            ->line('Changed by: '.$this->changedBy->name);

        if ($this->reason) {
            $mail->line('Reason: '.$this->reason);
        }

        return $mail->action('View Project', url("/admin/projects/{$this->project->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_no' => $this->project->no,
            'from_status' => $this->fromStatus?->value,
            'to_status' => $this->toStatus->value,
            'changed_by' => $this->changedBy->name,
            'reason' => $this->reason,
            'message' => "Project {$this->project->no} status changed to {$this->toStatus->label()}",
        ];
    }
}
