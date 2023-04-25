<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\{Project, ProjectTag};

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewProjectCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var Project
     */
    private Project $project;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Project $project)
    {
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
        return ['database', 'broadcast'];
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
            'type' => 'new_project',
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'project_tags' => $this->project->tags->map(function (ProjectTag $tag) {
                return $tag->tag->name;
            })->sortDesc()->slice(0, 3),
            'project_blockchain' => optional($this->project->blockchain)->name ?? null,
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
            'type' => 'new_project',
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'project_tags' => $this->project->tags->map(function (ProjectTag $tag) {
                return $tag->tag->name;
            })->sortDesc()->slice(0, 3),
            'project_blockchain' => optional($this->project->blockchain)->name ?? null,
        ]);
    }

    public function broadcastType(): string
    {
        return 'new_project';
    }

    /**
     * Determine which connections should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return [
            'database' => 'sync',
            'broadcast' => 'sync',
        ];
    }
}
