<?php

namespace App\Notifications\Social;

use App\Models\{
    TaskReward,
    AmbassadorTask,
};

use App\Notifications\Messages\DiscordMessage;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AmbassadorTaskCompletedSocialNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private AmbassadorTask $ambassadorTask;
    private string $ambassadorName;
    private string $managerName;

    private function discordProviderNotification(mixed $notifiable)
    {
        return collect($notifiable->discordProvider()?->notifications)->get('completedTask');
    }

    /**
     * Create a new notification instance.
     *
     * @param AmbassadorTask $ambassadorTask
     * @param string $ambassadorName
     * @param string $managerName
     */
    public function __construct(AmbassadorTask $ambassadorTask, string $ambassadorName, string $managerName)
    {
        $this->ambassadorTask = $ambassadorTask;
        $this->ambassadorName = $ambassadorName;
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
        return ($this->discordProviderNotification($notifiable)['active'] ?? null) ? ['discord'] : [];
    }

    /**
     * @param  mixed  $notifiable
     * @return DiscordMessage
     */
    public function toDiscord(mixed $notifiable): DiscordMessage
    {
        $taskUrl = sprintf(
            '%s/take-task/%s',
            config('app.ambassador_frontend_url'),
            $this->ambassadorTask->task_id,
        );

        $projectUrl = sprintf(
            '%s/projects/%s',
            config('app.ambassador_frontend_url'),
            $this->ambassadorTask->task->project_id,
        );

        $rewards = [];
        foreach ($this->ambassadorTask->task->rewards as $reward) {
            $rewards[] = $this->rewardToDiscordFormat($reward);
        }

        return (new DiscordMessage)
            ->embeds([
                [
                    'color' => 3553599,
                    'timestamp' => now()->toISOString(),
                    'title' => $this->ambassadorName . ' completed a task ✅',
                    'description' => 'Click on this message to join Catapult and start to contribute',
                    'fields' => [
                        [
                            'name' => 'Task',
                            'value' => '['.$this->ambassadorTask->task->name.']('.$taskUrl.')',
                            'inline' => false,
                        ],
                        [
                            'name' => 'Reviewer',
                            'value' => $this->managerName,
                            'inline' => false,
                        ],
                        [
                            'name' => 'Rewards',
                            'value' => implode('\n', $rewards),
                            'inline' => true,
                        ],
                    ],
                    'author' => [
                        'url' => $projectUrl,
                        'name' => 'Catapult',
                        'icon_url' => 'https://catapult.ac/favicons/favicon-32x32.png',
                    ],
                    'url' => $projectUrl,
                ],
            ])
            ->channelId($this->discordProviderNotification($notifiable)['channelId'] ?? null);
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
            //
        ];
    }

    private function rewardToDiscordFormat(TaskReward $reward)
    {
        if ($reward->type === 'coins') {
            return $reward->formatted_value.' coins';
        }

        if ($reward->type === 'discord_role') {
            return '<@&'.$reward->value.'>';
        }

        return $reward->value;
    }
}
