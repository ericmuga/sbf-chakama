<?php

namespace App\Notifications;

use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IssueClosedNotification extends Notification implements ShouldQueue
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
            ->subject("Issue Closed: {$this->issue->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line('An issue you may be tracking has been closed and is ready for QA review.')
            ->line("**{$this->issue->title}** ({$this->issue->portal_type?->getLabel()})")
            ->line($this->issue->details ?? '')
            ->line('QA Result: '.($this->issue->qa_test_result ?? '—'))
            ->action('Review in Issue Tracker', url('/admin/issues'))
            ->line('Please verify the fix and confirm closure.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'issue_id' => $this->issue->id,
            'title' => $this->issue->title,
            'message' => "Issue closed: {$this->issue->title}",
        ];
    }
}
