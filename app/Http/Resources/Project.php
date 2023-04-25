<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Project extends JsonResource
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
            'tags' => ProjectTag::collection($this->whenLoaded('tags')),
            'tasks' => Task::collection($this->whenLoaded('showcaseTasks')),
            'logo' => $this->whenLoaded('media', function () {
                return optional($this->media->first(function ($media) {
                    return $media->collection_name === 'logo';
                }))->getUrl();
            }),
            'banner' => $this->whenLoaded('media', function () {
                return optional($this->media->first(function ($media) {
                    return $media->collection_name === 'banner';
                }))->getUrl();
            }),
            'public' => $this->public,
            'coin_type' => new CoinType($this->whenLoaded('coinType')),
            'blockchain' => new Blockchain($this->whenLoaded('blockchain')),
            'description' => $this->description,
            'pool_amount' => $this->pool_amount,
            'social_links' => ProjectSocialLink::collection($this->whenLoaded('socialLinks')),
            'social_providers' => SocialProvider::collection($this->whenLoaded('socialProviders')),
            'medium_username' => $this->medium_username,
        ];
    }
}
