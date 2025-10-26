<?php

namespace App\Services\Instagram;

use App\Models\InstagramAccount;
use App\Services\Http\HttpClientExceptionDecorator;
use Illuminate\Support\Collection;

class InstagramApiService
{
    protected const BASE_URI = 'https://graph.instagram.com';

    public function __construct(
        protected HttpClientExceptionDecorator $httpClient
    ) {}

    public function getStories(InstagramAccount $account): Collection
    {
        if (!$account->access_token) {
            throw new \Exception("No access token available for account: {$account->username}");
        }

        $response = $this->httpClient->get(
            self::BASE_URI . "/me/stories",
            [
                'base_uri' => self::BASE_URI,
                'token' => $account->access_token,
            ]
        );

        return collect($response->json('data', []));
    }

    public function getStoryComments(InstagramAccount $account, string $storyId): Collection
    {
        if (!$account->access_token) {
            throw new \Exception("No access token available for account: {$account->username}");
        }

        $response = $this->httpClient->get(
            self::BASE_URI . "/{$storyId}/comments",
            [
                'base_uri' => self::BASE_URI,
                'token' => $account->access_token,
            ]
        );

        return collect($response->json('data', []));
    }

    public function blockUser(InstagramAccount $account, string $userId): bool
    {
        if (!$account->access_token) {
            throw new \Exception("No access token available for account: {$account->username}");
        }

        try {
            $this->httpClient->post(
                self::BASE_URI . "/me/blocked",
                [
                    'base_uri' => self::BASE_URI,
                    'token' => $account->access_token,
                    'json' => [
                        'user_id' => $userId,
                    ],
                ]
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserInfo(InstagramAccount $account, string $username): ?array
    {
        if (!$account->access_token) {
            throw new \Exception("No access token available for account: {$account->username}");
        }

        try {
            $response = $this->httpClient->get(
                self::BASE_URI . "/search",
                [
                    'base_uri' => self::BASE_URI,
                    'token' => $account->access_token,
                    'query' => [
                        'q' => $username,
                        'type' => 'user',
                    ],
                ]
            );

            $users = $response->json('data', []);
            
            return $users[0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
