<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PopularTask extends JsonResource
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
            'name' => $this->name,
            'project' => new Project($this->whenLoaded('project')),
            'activity' => new Activity($this->whenLoaded('activity')),
            'deadline' => $this->ended_at,
            'times_completed' => $this->when(isset($this->ambassador_tasks_completed_count), function () {
                return (int) $this->ambassador_tasks_completed_count;
            }, 0),
        ];
    }
}
