<?php

namespace App\Services\Instagram\Endpoints;

use App\Enums\RequestMethod;
use App\Models\Account;
use App\Services\Instagram\InstagramApiClient;
use Illuminate\Support\Collection;

class GetPostsByUsernameEndpoint
{
    public function __construct(
        private readonly InstagramApiClient $client,
        private readonly GetUserInfoEndpoint $getUserInfo,
    ) {}

    public function execute(Account $account, string $username): Collection
    {
        $userInfo = $this->getUserInfo->execute($account, $username);

        if (! $userInfo || ! isset($userInfo['id'])) {
            return collect([]);
        }

        $response = $this->client->request(RequestMethod::GET, $account, "/{$userInfo['id']}/media");

        return collect($response->json('data', []));
    }
}
