<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CoinType extends JsonResource
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
            'icon' => $this->whenLoaded('media', function () {
                return optional($this->media->first())->getUrl();
            }),
            'rpc_url' => $this->rpc_url,
            'chain_id' => $this->chain_id,
            'type_of_chain' => $this->type_of_chain,
            'block_explorer_url' => $this->block_explorer_url,
        ];
    }
}
