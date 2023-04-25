<?php

namespace App\Notifications;

use App\Models\AmbassadorTask;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var AmbassadorTask
     */
    private AmbassadorTask $ambassadorTask;

    /**
     * @var string
     */
    private string $managerName;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $managerName, AmbassadorTask $ambassadorTask)
    {
        $this->ambassadorTask = $ambassadorTask;
        $this->managerName = $managerName;
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
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $taskName = $this->ambassadorTask->task->name;

        return (new MailMessage)
                    ->line("Task \"$taskName\" was accepted by manager");
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
            'type' => 'task_status',
            'task_id' => $this->ambassadorTask->id,
            'task_name' => $this->ambassadorTask->task->name,
            'task_status' => 'done',
            'manager_name' => $this->managerName,
            'project_name' => $this->ambassadorTask->task->project->name,
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
            'type' => 'task_status',
            'task_id' => $this->ambassadorTask->id,
            'task_name' => $this->ambassadorTask->task->name,
            'task_status' => 'done',
            'manager_name' => $this->managerName,
            'project_name' => $this->ambassadorTask->task->project->name,
        ]);
    }

    public function broadcastType(): string
    {
        return 'task_status';
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
