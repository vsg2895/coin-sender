<?php

namespace App\Providers;

use App\Models\{
    User,
    Ambassador,
    AmbassadorTask,
};

use App\Channels\{
    DiscordChannel,
    DatabaseChannel,
};

use App\Services\{
    DiscordService,
    TelegramService,
};

use App\Contracts\{
    DiscordServiceContract,
    TelegramServiceContract,
};

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\{Validator, Notification};
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->instance(BaseDatabaseChannel::class, new DatabaseChannel());
        $this->app->bind(DiscordServiceContract::class, DiscordService::class);
        $this->app->bind(TelegramServiceContract::class, TelegramService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Notification::extend('discord', static function ($app) {
            return new DiscordChannel();
        });

        Relation::morphMap([
            'App\Models\User' => Ambassador::class,
            'App\Models\Manager' => User::class,
            'App\Models\UserTask' => AmbassadorTask::class,
        ]);

        Validator::extend('discord_invite', static function ($attribute, $value) {
            $code = getDiscordInviteCode($value);
            $invite = getDiscordInvite($code);
            return isset($invite['guild']);
        });

        Validator::extend('telegram_invite', static function ($attribute, $value) {
             $id = getTelegramChatId($value) ?? $value;
             $name = (str_starts_with($id, '@') ? $id : '@'.$id);

             $service = app(TelegramServiceContract::class);
             $response = $service->getChat(is_int($id) ? $id : $name);

             return !empty($response);
        });
    }
}
