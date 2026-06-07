<?php

namespace App\Notifications;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddedToProjectNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public ProjectMemberRole $role,
        public User $assigner,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $notifiable->is_admin
            ? route('filament.chakama.resources.projects.view', $this->project)
            : route('filament.member.resources.my-projects.view', $this->project);

        return (new MailMessage)
            ->subject("Added to project {$this->project->no}")
            ->line("You have been added to project **{$this->project->no} — {$this->project->name}**.")
            ->line('Role: '.$this->role->label())
            ->line('Added by: '.$this->assigner->name)
            ->action('View Project', $url);
    }

    public function toArray(object $notifiable): array
    {
        $url = $notifiable->is_admin
            ? route('filament.chakama.resources.projects.view', $this->project)
            : route('filament.member.resources.my-projects.view', $this->project);

        return [
            'project_id' => $this->project->id,
            'project_no' => $this->project->no,
            'role' => $this->role->value,
            'assigner' => $this->assigner->name,
            'message' => "You were added to project {$this->project->no} as {$this->role->label()}",
            'url' => $url,
        ];
    }
}
