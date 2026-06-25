<?php

namespace App\Notifications;

use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IssueLoggedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Issue $issue) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Issue Logged: {$this->issue->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A new issue has been logged in the issue tracker.')
            ->line("**{$this->issue->title}** ({$this->issue->portal_type?->getLabel()})")
            ->line($this->issue->details ?? '')
            ->line('Owner: '.($this->issue->issue_owner ?? '—'))
            ->action('Open Issue Tracker', url('/admin/issues'))
            ->line('Please action this issue and update its status when done.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'issue_id' => $this->issue->id,
            'title' => $this->issue->title,
            'message' => "New issue logged: {$this->issue->title}",
        ];
    }
}
