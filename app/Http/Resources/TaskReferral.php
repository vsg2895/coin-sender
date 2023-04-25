<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskReferral extends JsonResource
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
            'status' => $this->status_by_dates,
            'referrals' => AmbassadorReferral::collection($this->whenLoaded('referrals')),
            'referrals_count' => $this->referrals_count,
            'ambassadors_count' => $this->ambassadors_count,
        ];
    }
}
