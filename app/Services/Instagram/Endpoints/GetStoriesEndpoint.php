<?php

namespace App\Services\Instagram\Endpoints;

use App\Enums\RequestMethod;
use App\Models\Account;
use App\Services\Instagram\InstagramApiClient;
use Illuminate\Support\Collection;

class GetStoriesEndpoint
{
    public function __construct(private readonly InstagramApiClient $client) {}

    public function execute(Account $account): Collection
    {
        $response = $this->client->request(RequestMethod::GET, $account, '/me/stories');

        return collect($response->json('data', []));
    }
}
