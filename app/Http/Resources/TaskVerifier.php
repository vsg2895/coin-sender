<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskVerifier extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $types = collect($this->types);

        $tweet = $this->twitter_tweet;
        $space = $this->twitter_space;

        return [
            'id' => $this->id,
            'types' => $this->types,
            'invite_link' => $this->when($types->contains(function ($type) {
                return in_array($type, [
                    'discord_invite',
                    'telegram_invite',
                ]);
            }), $this->invite_link),
            $this->mergeWhen($types->contains('discord_invite'), [
                'discord_guild_id' => $this->discord_guild_id,
                'discord_guild_name' => $this->discord_guild_name,
            ]),
            $this->mergeWhen($types->contains('twitter_tweet'), [
                'tweet_words' => $this->tweet_words,
                'default_tweet' => $this->default_tweet,
            ]),
            $this->mergeWhen($types->contains(function ($type) {
                return in_array($type, [
                    'twitter_like',
                    'twitter_reply',
                    'twitter_retweet',
                ]);
            }), [
                'twitter_tweet' => $tweet,
                'twitter_tweet_id' => $tweet ? getTwitterTweetId($tweet) : null,
            ]),
            $this->mergeWhen($types->contains(fn ($type) => $type === 'twitter_space'), [
                'twitter_space' => $space,
                'twitter_space_id' => $space ? getTwitterSpaceId($space) : null,
            ]),
            'twitter_follow' => $this->when(
                $types->contains('twitter_follow'),
                getTwitterUsername($this->twitter_follow ?? ''),
            ),
            'default_reply' => $this->when($types->contains('twitter_reply'), $this->default_reply),
        ];
    }
}
