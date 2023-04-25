<?php

namespace App\Services\Socialite\Telegram;

use Exception;
use Throwable;

use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use SocialiteProviders\Telegram\Provider as BaseProvider;

class Provider extends BaseProvider
{
    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return ['bot', 'origin'];
    }

    /**
     * {@inheritdoc}
     * @throws Throwable
     */
    public function user(): \Laravel\Socialite\Two\User|\SocialiteProviders\Manager\OAuth2\User|null
    {
        $tgAuthResult = $this->getTgAuthResult();
        throw_if(!$tgAuthResult, InvalidTgAuthResultException::class);

        $validator = Validator::make($tgAuthResult, [
            'id'        => 'required|numeric',
            'auth_date' => 'required|date_format:U|before:1 day',
            'hash'      => 'required|size:64',
        ]);

        throw_if($validator->fails(), InvalidTgAuthResultException::class);
        return $this->mapUserToObject(Arr::except($tgAuthResult, ['auth_date', 'hash']));
    }

    /**
     * Redirect the user of the application to the provider's authentication screen.
     *
     * @return RedirectResponse
     */
    public function redirect(): RedirectResponse
    {
        return new RedirectResponse($this->getAuthUrl(null));
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthUrl($state): ?string
    {
        return $this->buildAuthUrlFromBase('https://oauth.telegram.org/auth', $state);
    }

    private function getTgAuthResult(): mixed
    {
        $data = $this->request->input('tgAuthResult');
        try {
            $data = preg_replace('/_/', '/', preg_replace('/-/', '+', $data));
            $pad = strlen($data) % 4;
            if ($pad > 1) {
                $data .= implode('', array_fill(0, 4 - $pad, '='));
            }

            return json_decode(base64_decode($data), true);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Get the GET parameters for the code request.
     *
     * @param  string|null  $state
     * @return array
     */
    protected function getCodeFields($state = null): array
    {
        $fields = [
            'bot_id' => $this->getConfig('bot'),
            'origin' => $this->getConfig('origin'),
            'return_to' => $this->redirectUrl,
            'request_access' => true,
        ];

        return array_merge($fields, $this->parameters);
    }

    /**
     * Build the authentication URL for the provider from the given base URL.
     *
     * @param  string  $url
     * @param  string  $state
     * @return string
     */
    protected function buildAuthUrlFromBase($url, $state): string
    {
        return $url.'?'.http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);
    }
}
