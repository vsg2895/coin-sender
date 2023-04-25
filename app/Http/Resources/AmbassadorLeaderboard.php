<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AmbassadorLeaderboard extends JsonResource
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
            'rank' => $this->level,
            'position' => $this->position,
            'tasks_done' => $this->when(isset($this->tasks_count), function () {
                return $this->tasks_count;
            }, 0),
            'tasks_points' => $this->when(isset($this->task_points_count), function () {
                return (int) $this->task_points_count;
            }, 0),
            'total_points' => $this->when(isset($this->all_task_points_count), function () {
                return (int) $this->all_task_points_count;
            }, 0),
        ];
    }
}
