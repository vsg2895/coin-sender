<?php

namespace App\Notifications;

use App\Models\Task;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var Task
     */
    private Task $task;

    /**
     * Create a new notification instance.
     *
     * @param Task $task
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
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
        return (new MailMessage)
            ->line("New task {$this->task->name} is available to you.");
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
            'type' => 'new_task',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'project_id' => $this->task->project->id,
            'project_name' => $this->task->project->name,
            'task_rewards' => $this->task->rewards->map(function ($reward) {
                $type = implode(' ', explode('_', $reward->type));
                return "{$type}: {$reward->formatted_value}";
            })->toArray(),
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
            'type' => 'new_task',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'project_id' => $this->task->project->id,
            'project_name' => $this->task->project->name,
            'task_rewards' => $this->task->rewards->map(function ($reward) {
                $type = implode(' ', explode('_', $reward->type));
                return "{$type}: {$reward->formatted_value}";
            })->toArray(),
        ]);
    }

    public function broadcastType(): string
    {
        return 'new_task';
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
