<?php

namespace App\Notifications\Social;

use App\Notifications\Messages\DiscordMessage;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class NewProjectCreatedSocialNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private string $projectName;

    private function discordProviderNotification(mixed $notifiable)
    {
        return collect($notifiable->discordProvider()?->notifications)->get('newProject');
    }

    private function telegramProviderNotification(mixed $notifiable)
    {
        return collect($notifiable->telegramProvider()?->notifications)->get('newProject');
    }

    /**
     * Create a new notification instance.
     *
     * @param string $projectName
     * @return void
     */
    public function __construct(string $projectName)
    {
        $this->projectName = $projectName;
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
     * @param  mixed  $notifiable
     * @return DiscordMessage
     */
    public function toDiscord(mixed $notifiable): DiscordMessage
    {
        return (new DiscordMessage)
            ->content("New project \"$this->projectName\" is created.")
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
            ->content("New project \"$this->projectName\" is created.");
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
}
