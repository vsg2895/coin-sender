<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AmbassadorActivity extends JsonResource
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
            'links' => AmbassadorActivityLink::collection($this->whenLoaded('links')),
            'status' => $this->status,
            'activity' => new Activity($this->whenLoaded('activity')),
        ];
    }
}
