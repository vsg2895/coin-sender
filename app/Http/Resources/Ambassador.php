<?php

namespace App\Http\Resources;

use App\Models\AmbassadorTask as AmbassadorTaskModel;
use Illuminate\Http\Resources\Json\JsonResource;

class Ambassador extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $need_points = config('levels.need_points')[$this->level] ?? 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'coins' => 0,
            'level' => $this->level,
            'email' => $this->email,
            'tasks' => AmbassadorTask::collection($this->whenLoaded('tasks')),
            'points' => $this->points,
            'avatar' => $this->whenLoaded('media', function () {
                return optional($this->media->first())->getUrl();
            }),
            'links' => AmbassadorActivityLink::collection($this->whenLoaded('links')),
            'skills' => AmbassadorSkill::collection($this->whenLoaded('skills')),
            'wallet' => $this->wallet,
            'country' => $this->whenLoaded('country', function () {
                return $this->country->country->name;
            }),
            'projects' => $this->whenLoaded('projectMembers', function () {
                return $this->projectMembers->pluck('project.name')->toArray();
            }),
            'languages' => UserLanguage::collection($this->whenLoaded('languages')),
            'activities' => AmbassadorActivity::collection($this->whenLoaded('activities')),
            'social_links' => UserSocialLink::collection($this->whenLoaded('socialLinks')),
            'social_providers' => SocialProvider::collection($this->whenLoaded('socialProviders')),
            'activity_links' => AmbassadorActivityLink::collection($this->whenLoaded('activityLinks')),
            'next_level' => !($need_points === 0) && ($this->points >= $need_points || (!is_null($this->position) && $this->position <= config('app.minimum_leaderboard_place_level_up'))),
            'need_points' => $need_points,
            'total_tasks' => $this->when(isset($this->tasks_count), function () {
                return $this->tasks_count;
            }, 0),
            'total_points' => $this->total_points,
            'registered_at' => $this->created_at,
            'total_balance' => 0,
            'total_rewards' => 0,
            'invitation_status' => $this->whenLoaded('invitation', function () {
                return optional($this->invitation->first())->status;
            }),
            'has_failed_deadline' => (bool) $this->has_failed_deadline,
        ];
    }
}
