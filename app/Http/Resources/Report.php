<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Report extends JsonResource
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
            'type' => $this->type,
            'text' => $this->text,
            'project_name' => $this->whenLoaded('project', fn () => $this->project->name),
            'ambassador_name' => $this->whenLoaded('ambassador', fn () => $this->ambassador->name),
            'created_at' => $this->created_at,
        ];
    }
}
