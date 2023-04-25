<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TaskRewardServiceProvider extends ServiceProvider
{
    /**
     * The verifier mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected array $rewards = [
        'coins' => 'App\TaskRewards\CoinTaskReward',
        'discord_role' => 'App\TaskRewards\DiscordRoleTaskReward',
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerRewards();
    }

    /**
     * Register the application's verifiers.
     *
     * @return void
     */
    public function registerRewards(): void
    {
        foreach ($this->rewards as $key => $value) {
            $this->app->bind($key, $value);
        }
    }
}
