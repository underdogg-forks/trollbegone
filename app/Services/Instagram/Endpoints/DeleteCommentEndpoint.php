<?php

namespace App\Services\Instagram\Endpoints;

use App\Enums\RequestMethod;
use App\Models\Account;
use App\Services\Instagram\InstagramApiClient;

class DeleteCommentEndpoint
{
    public function __construct(private readonly InstagramApiClient $client) {}

    public function execute(Account $account, string $commentId): bool
    {
        $this->client->request(RequestMethod::DELETE, $account, "/{$commentId}");

        return true;
    }
}
