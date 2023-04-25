<?php

namespace App\Providers;

use App\Services\Socialite\Twitter\TwitterExtendSocialite;
use App\Services\Socialite\Telegram\TelegramExtendSocialite;
use App\Services\Socialite\DiscordBot\DiscordBotExtendSocialite;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        SocialiteWasCalled::class => [
            DiscordExtendSocialite::class.'@handle',
            TwitterExtendSocialite::class.'@handle',
            TelegramExtendSocialite::class.'@handle',
            DiscordBotExtendSocialite::class.'@handle',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
