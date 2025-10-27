<?php

namespace App\Providers;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

/**
 * Instagram Socialite Provider for OAuth authentication with Instagram Graph API.
 * This provider implements the OAuth 2.0 flow for Instagram Business accounts.
 */
class InstagramProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'instagram_basic',
        'instagram_manage_comments',
        'instagram_manage_insights',
    ];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ',';

    /**
     * Get the authentication URL for the provider.
     *
     * @param  string  $state
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.instagram.com/oauth/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.instagram.com/oauth/access_token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string  $token
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(
            'https://graph.instagram.com/me',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
                'query' => [
                    'fields' => 'id,username,account_type',
                ],
            ]
        );

        return json_decode($response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['username'] ?? null,
            'name' => $user['username'] ?? null,
            'email' => null, // Instagram doesn't provide email through this API
            'avatar' => null, // Would need additional API call to get profile picture
        ]);
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     */
    public function getAccessTokenResponse($code): array
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'form_params' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     */
    protected function getTokenFields($code): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
        ];
    }
}
