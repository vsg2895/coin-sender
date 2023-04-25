<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PendingClaim extends JsonResource
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
            'amount' => $this->value,
            'symbol' => $this->whenLoaded('wallet', function () {
                return $this->wallet->coinType->name;
            }),
            'talent' => $this->whenLoaded('ambassador', function () {
                return $this->ambassador->name;
            }),
            'created_at' => $this->created_at,
        ];
    }
}
