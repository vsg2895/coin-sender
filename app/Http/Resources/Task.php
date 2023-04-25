<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Task extends JsonResource
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
            'text' => $this->text,
            'images' => File::collection($this->whenLoaded('media')),
            'project' => new Project($this->whenLoaded('project')),
            'rewards' => TaskReward::collection($this->whenLoaded('rewards')),
            'verifier' => new TaskVerifier($this->whenLoaded('verifier')),
            'activity' => new Activity($this->whenLoaded('activity')),
            'priority' => $this->priority,
            'coin_type' => new CoinType($this->whenLoaded('coinType')),
            'conditions' => TaskCondition::collection($this->whenLoaded('conditions')),
            'min_level' => $this->min_level,
            'max_level' => $this->max_level,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'is_invite_friends' => $this->is_invite_friends,
            'number_of_winners' => $this->number_of_winners,
            'number_of_invites' => $this->number_of_invites,
            'level_coefficient' => $this->level_coefficient,
            'number_of_participants' => $this->number_of_participants,
            'working_users' => $this->whenLoaded('ambassadorTasksInWork', function () {
                return Ambassador::collection($this->ambassadorTasksInWork->map(function ($ambassadorTaskInWork) {
                    return $ambassadorTaskInWork->ambassador;
                }));
            }),
            'assigned_users' => Ambassador::collection($this->whenLoaded('ambassadorAssignments')),
            'autovalidate' => $this->autovalidate,
            'status_by_dates' => $this->status_by_dates,
            'verifier_driver' => $this->verifier_driver,
            'editing_not_available' => $this->editing_not_available,
        ];
    }
}
