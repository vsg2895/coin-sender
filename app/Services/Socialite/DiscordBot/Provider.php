<?php

namespace App\Services\Socialite\DiscordBot;

use Illuminate\Http\RedirectResponse;
use SocialiteProviders\Discord\Provider as BaseProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends BaseProvider
{
    public const IDENTIFIER = 'DISCORD_BOT';

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return array_merge(parent::additionalConfigKeys(), ['guild', 'permissions', 'disable_guild_select']);
    }

    /**
     * Redirect the user of the application to the provider's authentication screen.
     *
     * @return RedirectResponse
     */
    public function redirect(): RedirectResponse
    {
        $state = null;
        if ($this->request->has('project_id')) {
            $state = $this->request->get('project_id');
        }

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://discord.com/api/guilds/' . $this->request->input('guild_id'),
            [
                'headers' => [
                    'Authorization' => 'Bot '.config('services.discord_bot.token'),
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null): array
    {
        if ($this->getConfig('disableGuildSelect') && !$this->getConfig('guild')) {
            throw new GuildRequiredException();
        }

        return [
            'client_id' => $this->clientId,
            'scope' => 'bot',
            'permissions' => $this->getConfig('permissions'),
            'response_type' => 'code',
            'state' => $state,
            'guild_id' => $this->getConfig('guild'),
            'redirect_uri' => $this->redirectUrl,
            'disable_guild_select' => $this->getConfig('disableGuildSelect') ? 'true' : 'false',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): \Laravel\Socialite\Two\User|User
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'name'     => $user['name'],
            'email'    => null,
            'avatar'   => null,
            'nickname' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildAuthUrlFromBase($url, $state): string
    {
        return $url.'?'.http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);
    }
}
