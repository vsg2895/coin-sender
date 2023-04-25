<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserNotification extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'read' => $this->read(),
            'data' => [
                'task_id' => $this->data['task_id'] ?? null,
                'task_name' => $this->data['task_name'] ?? null,
                'project_id' => $this->data['project_id'] ?? null,
                'project_name' => $this->data['project_name'] ?? null,
                'activity_name' => $this->data['activity_name'] ?? null,
                'ambassador_id' => $this->data['ambassador_id'] ?? null,
                'ambassador_name' => $this->data['ambassador_name'] ?? null,
                'invitation_status' => $this->invitation_status,
            ],
            'buttons' => [
                'accept' => $this->data['buttons']['accept'] ?? null,
                'reject' => $this->data['buttons']['reject'] ?? null,
            ],
            'created_at' => $this->created_at,
            'invitation_token' => $this->invitation_token,
        ];
    }
}
