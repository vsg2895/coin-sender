<?php

namespace App\Services\Socialite\Telegram;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TelegramExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('telegram', Provider::class);
    }
}
