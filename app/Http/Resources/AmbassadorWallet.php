<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AmbassadorWallet extends JsonResource
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
            'balance' => $this->balance,
            'address' => $this->address,
            'coin_type' => new CoinType($this->whenLoaded('coinType')),
            'is_primary' => $this->is_primary,
            'balance_in_usd' => 0,
        ];
    }
}
