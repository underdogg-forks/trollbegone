<?php

namespace App\Services\Instagram\Endpoints;

use App\Enums\RequestMethod;
use App\Models\Account;
use App\Services\Instagram\InstagramApiClient;

class BlockUserEndpoint
{
    public function __construct(private readonly InstagramApiClient $client) {}

    public function execute(Account $account, string $userId): bool
    {
        $this->client->request(RequestMethod::POST, $account, '/me/blocked', [
            'json' => ['user_id' => $userId],
        ]);

        return true;
    }
}
