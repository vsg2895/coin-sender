<?php

return [
    'verifier' => [
        'types' => [
            'discord' => [
                'discord_invite',
            ],
            'twitter' => [
                'twitter_like',
                'twitter_tweet',
                'twitter_reply',
                'twitter_space',
                'twitter_follow',
                'twitter_retweet',
            ],
            'telegram' => [
                'telegram_invite',
            ],
        ],
        'drivers' => [
            'discord',
            'twitter',
            'telegram',
        ],
    ],
    'priorities' => ['low', 'medium', 'high'],
];
