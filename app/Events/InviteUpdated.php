<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;

class InviteUpdated
{
    use SerializesModels;

    private int $userId;
    private string $token;
    private string $status;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $userId, string $token, string $status)
    {
        $this->token = $token;
        $this->userId = $userId;
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ambassador.'.$this->userId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'invite.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'token' => $this->token,
            'status' => $this->status,
        ];
    }
}
