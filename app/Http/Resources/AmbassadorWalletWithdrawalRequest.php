<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AmbassadorWalletWithdrawalRequest extends JsonResource
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
            'date' => $this->created_at,
            'value' => $this->value,
            'status' => $this->status,
            'tx_hash' => $this->tx_hash,
            'ambassador' => new Ambassador($this->whenLoaded('ambassador')),
            'ambassador_wallet' => new AmbassadorWallet($this->whenLoaded('wallet')),
        ];
    }
}
