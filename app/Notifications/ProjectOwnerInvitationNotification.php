<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectOwnerInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var string
     */
    private $projectName;

    /**
     * @var string
     */
    private $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $token, string $projectName)
    {
        $this->token = $token;
        $this->projectName = $projectName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line("You invited to become owner of project \"$this->projectName\"")
            ->action('Apply Invite', sprintf(
                '%s/%s/%s',
                config('app.manager_frontend_url'),
                'accept-invitation',
                $this->token,
            ));
    }
}
