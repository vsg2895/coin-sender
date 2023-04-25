<?php

namespace App\Services\Socialite\DiscordBot;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DiscordBotExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('discord_bot', Provider::class);
    }
}
