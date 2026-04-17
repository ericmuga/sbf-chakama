<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNewUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $temporaryPassword,
        public readonly string $portalUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to '.config('app.name').' — Your Account Details')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your member account has been created. You can now access the member portal.')
            ->line('**Your Login Details:**')
            ->line("Email: {$notifiable->email}")
            ->line("Temporary Password: `{$this->temporaryPassword}`")
            ->action('Log In & Set Your Password', $this->portalUrl)
            ->line('For security, please change your password immediately after your first login.')
            ->line('If you did not expect this account, please contact us immediately.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Welcome! Your account has been created.',
        ];
    }
}
