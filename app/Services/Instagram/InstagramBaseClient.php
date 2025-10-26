<?php

namespace App\Services\Instagram;

use App\Models\InstagramAccount;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Http\HttpClientException;
use Exception;
use Illuminate\Http\Client\Response;

/**
 * Base client for Instagram API interactions.
 * Provides common functionality for making authenticated requests to Instagram's Graph API.
 */
abstract class InstagramBaseClient
{
    protected const BASE_URI = 'https://graph.instagram.com';

    public function __construct(
        protected HttpClientExceptionDecorator $httpClient
    ) {}

    /**
     * Make an authenticated GET request to the Instagram API.
     *
     * @param InstagramAccount $account The Instagram account to authenticate with
     * @param string $endpoint The API endpoint (will be appended to BASE_URI)
     * @param array $queryParams Optional query parameters
     * @return Response
     * @throws HttpClientException
     * @throws Exception If no access token is available
     */
    protected function get(InstagramAccount $account, string $endpoint, array $queryParams = []): Response
    {
        $this->ensureAccessToken($account);

        $options = [
            'base_uri' => self::BASE_URI,
            'token' => $account->access_token,
        ];

        if (!empty($queryParams)) {
            $options['query'] = $queryParams;
        }

        return $this->httpClient->get(self::BASE_URI . $endpoint, $options);
    }

    /**
     * Make an authenticated POST request to the Instagram API.
     *
     * @param InstagramAccount $account The Instagram account to authenticate with
     * @param string $endpoint The API endpoint (will be appended to BASE_URI)
     * @param array $data The data to send in the request body
     * @return Response
     * @throws HttpClientException
     * @throws Exception If no access token is available
     */
    protected function post(InstagramAccount $account, string $endpoint, array $data = []): Response
    {
        $this->ensureAccessToken($account);

        $options = [
            'base_uri' => self::BASE_URI,
            'token' => $account->access_token,
        ];

        if (!empty($data)) {
            $options['json'] = $data;
        }

        return $this->httpClient->post(self::BASE_URI . $endpoint, $options);
    }

    /**
     * Make an authenticated PUT request to the Instagram API.
     *
     * @param InstagramAccount $account The Instagram account to authenticate with
     * @param string $endpoint The API endpoint (will be appended to BASE_URI)
     * @param array $data The data to send in the request body
     * @return Response
     * @throws HttpClientException
     * @throws Exception If no access token is available
     */
    protected function put(InstagramAccount $account, string $endpoint, array $data = []): Response
    {
        $this->ensureAccessToken($account);

        $options = [
            'base_uri' => self::BASE_URI,
            'token' => $account->access_token,
        ];

        if (!empty($data)) {
            $options['json'] = $data;
        }

        return $this->httpClient->put(self::BASE_URI . $endpoint, $options);
    }

    /**
     * Make an authenticated DELETE request to the Instagram API.
     *
     * @param InstagramAccount $account The Instagram account to authenticate with
     * @param string $endpoint The API endpoint (will be appended to BASE_URI)
     * @return Response
     * @throws HttpClientException
     * @throws Exception If no access token is available
     */
    protected function delete(InstagramAccount $account, string $endpoint): Response
    {
        $this->ensureAccessToken($account);

        $options = [
            'base_uri' => self::BASE_URI,
            'token' => $account->access_token,
        ];

        return $this->httpClient->delete(self::BASE_URI . $endpoint, $options);
    }

    /**
     * Ensure the Instagram account has a valid access token.
     *
     * @param InstagramAccount $account
     * @return void
     * @throws Exception If no access token is available
     */
    protected function ensureAccessToken(InstagramAccount $account): void
    {
        if (!$account->access_token) {
            throw new Exception("No access token available for account: {$account->username}");
        }
    }
}
