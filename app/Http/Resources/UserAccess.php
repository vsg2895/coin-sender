<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAccess extends JsonResource
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
            'role' => $this->whenLoaded('allRoles', function () {
                return new Role($this->allRoles->first());
            }),
            'status' => $this->whenLoaded('invitation', function () {
                return optional($this->invitation->first())->status;
            }),
            'project_id' => $this->whenLoaded('invitation', function () {
                return optional(optional($this->invitation->first())->project)->id;
            }),
            'project_name' => $this->whenLoaded('invitation', function () {
                return optional(optional($this->invitation->first())->project)->name;
            }),
        ];
    }
}
