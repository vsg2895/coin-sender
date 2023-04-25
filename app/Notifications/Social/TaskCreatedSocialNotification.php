<?php

namespace App\Notifications\Social;

use App\Models\Task;
use App\Models\TaskReward;
use App\Notifications\Messages\DiscordMessage;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class TaskCreatedSocialNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Task $task;

    private function discordProviderNotification(mixed $notifiable)
    {
        return collect($notifiable->discordProvider()?->notifications)->get('newTask');
    }

    private function telegramProviderNotification(mixed $notifiable)
    {
        return collect($notifiable->telegramProvider()?->notifications)->get('newTask');
    }

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
        $channels = [];

        if ($this->discordProviderNotification($notifiable)['active'] ?? null) {
            $channels[] = 'discord';
        }

        if ($this->telegramProviderNotification($notifiable)['active'] ?? null) {
            $channels[] = 'telegram';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return DiscordMessage
     */
    public function toDiscord(mixed $notifiable): DiscordMessage
    {
        $url = $this->getTaskUrl();
        $rewards = [];

        foreach ($this->task->rewards as $reward) {
            $rewards[] = $this->rewardToDiscordFormat($reward);
        }

        return (new DiscordMessage)
            ->embeds([
                [
                    'color' => 3553599,
                    'timestamp' => now()->toISOString(),
                    'title' => $this->task->name,
                    'fields' => [
                        [
                            'name' => 'Rewards',
                            'value' => implode('\\n', $rewards),
                            'inline' => true,
                        ],
                    ],
                    'author' => [
                        'url' => $url,
                        'name' => 'Catapult | New Task',
                        'icon_url' => 'https://catapult.ac/favicons/favicon-32x32.png',
                    ],
                    'footer' => [
                        'text' => 'Login to catapult to claim your reward',
                    ],
                    'url' => $url,
                ],
            ])
            ->channelId($this->discordProviderNotification($notifiable)['channelId'] ?? null);
    }

    /**
     * @param  mixed  $notifiable
     * @return TelegramMessage
     */
    public function toTelegram(mixed $notifiable): TelegramMessage
    {
        return (new TelegramMessage)
            ->token(config('services.telegram.client_secret'))
            ->content('New task **[' . $this->task->name . ']('. $this->getTaskUrl() .')** is available to you.');
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

    private function getTaskUrl(): string
    {
        return sprintf(
            '%s/take-task/%s',
            config('app.ambassador_frontend_url'),
            $this->task->id,
        );
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
