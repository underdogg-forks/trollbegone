<?php

namespace App\Services\Instagram;

use App\Enums\RequestMethod;
use App\Models\Account;
use App\Services\Http\ApiClient;
use Illuminate\Http\Client\Response;

class InstagramApiClient
{
    public const BASE_URI = 'https://graph.instagram.com';

    public function __construct(private readonly ApiClient $httpClient) {}

    public function request(
        RequestMethod|string $method,
        Account $account,
        string $endpoint,
        array $options = []
    ): Response {
        if (! $account->access_token) {
            throw new \Exception("No access token available for account: {$account->username}");
        }

        $endpoint = '/'.ltrim($endpoint, '/');

        $options = array_merge([
            'base_uri' => self::BASE_URI,
            'token' => $account->access_token,
        ], $options);

        return $this->httpClient->request(
            RequestMethod::normalize($method),
            self::BASE_URI.$endpoint,
            $options
        );
    }
}
