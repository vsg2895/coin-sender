<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AmbassadorTop extends JsonResource
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
            'level' => $this->level,
            'total' => $this->when(isset($this->all_task_points_count), function () {
                return (int) $this->all_task_points_count;
            }, 0),
            'avatar' => $this->whenLoaded('media', function () {
                return optional($this->media->first())->getUrl();
            }),
            'activities' => AmbassadorActivity::collection($this->whenLoaded('activities')),
        ];
    }
}
