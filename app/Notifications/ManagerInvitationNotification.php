<?php

namespace App\Notifications;

use App\Models\{Project, Invitation};

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ManagerInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var string
     */
    private string $token;

    /**
     * @var Project
     */
    private Project $project;

    /**
     * @var string
     */
    private string $roleName;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $token, string $roleName, Project $project)
    {
        $this->token = $token;
        $this->project = $project;
        $this->roleName = $roleName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        $channels = ['mail'];

        if ($this->roleName === 'Catapult Manager') {
            $channels[] = 'database';
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line("You invited to become manager with role \"$this->roleName\"")
            ->action('Apply Invite', $this->getAcceptUrl());
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'invite_to_join',
            'buttons' => [
                'accept' => $this->getAcceptUrl(),
            ],
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'invitation_token' => $this->token,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'invite_to_join',
            'buttons' => [
                'accept' => $this->getAcceptUrl(),
            ],
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'invitation_token' => $this->token,
            'invitation_status' => Invitation::STATUS_PENDING,
        ]);
    }

    public function broadcastType(): string
    {
        return 'invite_to_join';
    }

    private function getAcceptUrl(): string
    {
        return sprintf(
            '%s/%s/%s',
            config('app.manager_frontend_url'),
            'accept-invitation',
            $this->token,
        );
    }
}
