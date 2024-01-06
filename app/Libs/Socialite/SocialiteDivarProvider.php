<?php

namespace App\Libs\Socialite;

use Illuminate\Support\Str;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;

class SocialiteDivarProvider extends AbstractProvider
{

    protected $scopes = [
        'ADDON_USER_APPROVED',
        'USER_PHONE',
    ];
    protected $scopeSeparator = ' ';

    public function getAuthUrl($state)
    {
        $redirect = url('/api/v1/authback');
        $state2 = uniqid();
        return 'https://open-platform-redirect.divar.ir/oauth?response_type=code&client_id=gata&redirect_uri='.$redirect.'&scope=USER_PHONE&state='.$state2;//ADDON_USER_APPROVED__AZTH74V2
    }

    protected function getTokenUrl()
    {
        return $this->getAuthUrl() . '/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl() . '/userInfo', [
            'headers' => [
                'cache-control' => 'no-cache',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function mapUserToObject(array $user)
    {
        // TODO: Implement mapUserToObject() method.
    }
}
