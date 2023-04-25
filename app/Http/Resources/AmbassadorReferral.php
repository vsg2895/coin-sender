<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AmbassadorReferral extends JsonResource
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
            'created_at' => $this->created_at,
            'referral_name' => $this->whenLoaded('referral', function () {
                return $this->referral->name;
            }),
            'ambassador_name' => $this->whenLoaded('ambassador', function () {
                return $this->ambassador->name;
            }),
        ];
    }
}
