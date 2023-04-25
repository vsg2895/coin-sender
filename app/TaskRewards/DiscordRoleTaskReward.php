<?php

namespace App\TaskRewards;

use App\Models\{Task, Ambassador};
use App\Contracts\{TaskRewardContract, DiscordServiceContract};

class DiscordRoleTaskReward extends BaseTaskReward implements TaskRewardContract
{
    public function giveTo(Ambassador $ambassador, Task $task)
    {
        $userSocialProvider = $ambassador->socialProviders()
            ->where('provider_name', 'discord')
            ->first();

        $projectSocialProvider = $task->project->socialProviders()
            ->where('provider_name', 'discord_bot')
            ->first();

        if ($userSocialProvider && $projectSocialProvider) {
            app(DiscordServiceContract::class)->giveRole(
                $this->taskReward->value,
                $projectSocialProvider->provider_id,
                $userSocialProvider->provider_id,
            );
        }
    }
}
