<?php

namespace App\Providers;

use App\Contracts\TwitterServiceContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class TwitterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind('App\Contracts\TwitterServiceContract', 'App\Services\TwitterService');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        Validator::extend('twitter_tweet', function ($attribute, $value) {
            $service = app(TwitterServiceContract::class);
            $response = $service->tweet(getTwitterTweetId($value));
            return !empty($response);
        });

        Validator::extend('twitter_space', function ($attribute, $value) {
            $service = app(TwitterServiceContract::class);
            $response = $service->space(getTwitterSpaceId($value));
            return !empty($response);
        });

        Validator::extend('twitter_handle', function ($attribute, $value) {
            $service = app(TwitterServiceContract::class);
            $response = $service->user(getTwitterUsername($value));
            return !empty($response);
        });
    }
}
