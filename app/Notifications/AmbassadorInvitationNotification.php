<?php

namespace App\Notifications;

use App\Models\{Project, Invitation};

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class AmbassadorInvitationNotification extends Notification implements ShouldQueue
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
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $token, Project $project)
    {
        $this->token = $token;
        $this->project = $project;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $projectName = $this->project->name;

        return (new MailMessage)
            ->line("Hello, you are invited to work with \"$projectName\" project team")
            ->action('Accept', $this->getAcceptUrl())
            ->line(new HtmlString('Ignore this message, if you aren\'t Catapult if you aren’t Catapult participant. If you want to know more about us, check <a href="https://catapult.ac">website</a>'));
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
                'reject' => $this->getRejectUrl(),
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
                'reject' => $this->getRejectUrl(),
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
            config('app.ambassador_frontend_url'),
            'accept-invitation',
            $this->token,
        );
    }

    private function getRejectUrl(): string
    {
        return sprintf(
            '%s/%s/%s',
            config('app.ambassador_frontend_url'),
            'reject-invitation',
            $this->token,
        );
    }

    /**
     * Determine which connections should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return [
            'mail' => 'database',
            'database' => 'sync',
            'broadcast' => 'sync',
        ];
    }
}
