<?php

namespace App\Channels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Notifications\Notification;

class DatabaseChannel extends BaseDatabaseChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  Notification  $notification
     *
     * @return Model|array
     */
    public function buildPayload($notifiable, Notification $notification): Model|array
    {
        $data = $this->getData($notifiable, $notification);

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? get_class($notification),
            'data' => $data,
            'read_at' => null,
            'invitation_token' => $data['invitation_token'] ?? null,
        ];
    }
}
