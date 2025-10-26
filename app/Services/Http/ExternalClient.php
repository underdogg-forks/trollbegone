<?php

namespace App\Services\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * ExternalClient provides a unified interface for making HTTP requests to external APIs.
 * This client mimics the interface of GuzzleHttp\Client with a single request() method.
 */
class ExternalClient
{
    /**
     * Send an HTTP request to an external API.
     *
     * @param  string  $method  HTTP method (GET, POST, PUT, DELETE, PATCH, etc.)
     * @param  string  $url  The URL to send the request to
     * @param  array  $options  Request options including:
     *                          - headers: array of HTTP headers
     *                          - token: Bearer token for authentication
     *                          - base_uri: Base URL for the request
     *                          - timeout: Request timeout in seconds (default: 30)
     *                          - connect_timeout: Connection timeout in seconds (default: 10)
     *                          - json: JSON data to send in the request body
     *                          - query: Query parameters for the request
     */
    public function request(
        string $method,
        string $url,
        array $options = []
    ): Response {
        $client = $this->buildClient($options);

        return $client->send($method, $url, $options);
    }

    /**
     * Build and configure the HTTP client with the provided options.
     *
     * @param  array  $options  Configuration options for the HTTP client
     */
    protected function buildClient(array $options = []): PendingRequest
    {
        $client = Http::timeout($options['timeout'] ?? 30)
            ->connectTimeout($options['connect_timeout'] ?? 10);

        if (isset($options['headers'])) {
            $client->withHeaders($options['headers']);
        }

        if (isset($options['token'])) {
            $client->withToken($options['token']);
        }

        if (isset($options['base_uri'])) {
            $client->baseUrl($options['base_uri']);
        }

        return $client;
    }
}
