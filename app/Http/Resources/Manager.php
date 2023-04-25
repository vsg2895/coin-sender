<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Manager extends JsonResource
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
            'roles' => Role::collection($this->whenLoaded('allRoles')),
            'status' => $this->whenLoaded('invitation', function () {
                return optional($this->invitation->first())->status;
            }),
            'projects' => $this->whenLoaded('projectMembers', function () {
                return $this->projectMembers->pluck('project.name')->toArray();
            }),
            'created_at' => $this->created_at,
            'tasks_count' => $this->when(isset($this->tasks_count), function () {
                return $this->tasks_count;
            }, 0),
        ];
    }
}
