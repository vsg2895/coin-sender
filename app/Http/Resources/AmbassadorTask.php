<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AmbassadorTask extends JsonResource
{
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
            'user' => new Ambassador($this->whenLoaded('ambassador')),
            'task' => new Task($this->whenLoaded('task')),
            'report' => $this->report,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'task_id' => $this->task_id,
            'invites' => [
                'code' => $this->referral_code,
                'count' => $this->when(isset($this->referrals_count), fn () => $this->referrals_count, 0),
            ],
            'manager' => new Presentable($this->whenLoaded('manager')),
            'manager_id' => $this->manager_id,
            'reported_at' => $this->reported_at,
            'completed_at' => $this->completed_at,
            'report_attachments' => File::collection($this->whenLoaded('media')),
        ];
    }
}
