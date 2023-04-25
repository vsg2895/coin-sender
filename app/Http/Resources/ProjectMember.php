<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectMember extends JsonResource
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
            'role' => $this->whenLoaded('roles', function () {
                return new Role($this->roles->first());
            }),
            'status' => $this->whenLoaded('invitation', function () {
                return optional($this->invitation->first())->status;
            }),
            'created_at' => $this->created_at,
            'tasks_count' => $this->when(isset($this->tasks_count), function () {
                return $this->tasks_count;
            }, 0),
        ];
    }
}
