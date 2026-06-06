<?php

namespace App\Services\Instagram\Endpoints;

use App\Enums\RequestMethod;
use App\Models\Account;
use App\Services\Instagram\InstagramApiClient;

class GetUserInfoEndpoint
{
    public function __construct(private readonly InstagramApiClient $client) {}

    public function execute(Account $account, string $username): ?array
    {
        $response = $this->client->request(RequestMethod::GET, $account, '/search', [
            'query' => ['q' => $username, 'type' => 'user'],
        ]);

        $users = $response->json('data', []);

        return $users[0] ?? null;
    }
}
