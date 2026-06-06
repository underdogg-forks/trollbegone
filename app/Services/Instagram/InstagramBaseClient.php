<?php

namespace App\Services\Instagram;

use App\Enums\RequestMethod;
use App\Models\Account;
use App\Services\Http\ApiClient;
use App\Services\Http\HttpClientException;
use Exception;
use Illuminate\Http\Client\Response;

/**
 * Base client for Instagram API interactions.
 * Provides common functionality for making authenticated requests to Instagram's Graph API.
 * Uses a single request() method for all HTTP operations.
 */
abstract class InstagramBaseClient
{
    protected const BASE_URI = 'https://graph.instagram.com';

    public function __construct(
        protected ApiClient $httpClient
    ) {}

    /**
     * Make an authenticated request to the Instagram API.
     *
     * @param  RequestMethod|string  $method  HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param  Account  $account  The account to authenticate with
     * @param  string  $endpoint  The API endpoint (will be appended to BASE_URI)
     * @param  array  $options  Optional request options (query, json, etc.)
     *
     * @throws HttpClientException
     * @throws Exception If no access token is available
     */
    protected function request(
        RequestMethod|string $method,
        Account $account,
        string $endpoint,
        array $options = []
    ): Response {
        // Guard clause - ensure access token exists
        if (! $account->access_token) {
            throw new Exception("No access token available for account: {$account->username}");
        }

        $httpMethod = RequestMethod::normalize($method);

        // Normalize endpoint to ensure leading slash
        $endpoint = '/'.ltrim($endpoint, '/');

        // Build options with authentication token
        $options = array_merge([
            'base_uri' => self::BASE_URI,
            'token' => $account->access_token,
        ], $options);

        return $this->httpClient->request($httpMethod, self::BASE_URI.$endpoint, $options);
    }
}
