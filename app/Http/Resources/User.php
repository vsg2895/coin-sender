<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
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
            'roles' => Role::collection($this->whenLoaded('allRoles')),
            'email' => $this->email,
            'avatar' => $this->whenLoaded('media', function () {
                return optional($this->media->first())->getUrl();
            }),
            'country' => $this->whenLoaded('country', function () {
                return $this->country->country->name;
            }),
            'projects' => $this->whenLoaded('projectMembers', function () {
                return Presentable::collection($this->projectMembers->map(function ($projectMember) {
                    return $projectMember->project;
                }));
            }),
            'languages' => UserLanguage::collection($this->whenLoaded('languages')),
            'self_project' => new Presentable($this->whenLoaded('selfProject')),
            'social_links' => UserSocialLink::collection($this->whenLoaded('socialLinks')),
            'social_providers' => SocialProvider::collection($this->whenLoaded('socialProviders')),
            'created_tasks' => $this->when(isset($this->tasks_count), function () {
                return $this->tasks_count;
            }, 0),
            'checked_tasks' => $this->when(isset($this->checked_tasks_count), function () {
                return $this->checked_tasks_count;
            }, 0),
            'deadlines_violated' => 0,
        ];
    }
}
