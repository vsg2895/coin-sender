<?php

namespace App\Channels;

use App\Contracts\DiscordServiceContract;
use Illuminate\Notifications\Notification;

class DiscordChannel
{
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     *
     * @return bool
     */
    public function send(mixed $notifiable, Notification $notification): bool
    {
        if (method_exists($notifiable, 'routeNotificationForDiscord')) {
            $id = $notifiable->routeNotificationForDiscord($notifiable);
        } else {
            $id = $notifiable->getKey();
        }

        $data = $notification->toDiscord($notifiable);
        if (empty($data->channelId)) {
            return false;
        }

        return app(DiscordServiceContract::class)->sendMessageToGuild($id, [
            'embeds' => $data->embeds,
            'content' => $data->content,
            'channelId' => $data->channelId,
        ]);
    }
}
