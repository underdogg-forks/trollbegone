<?php

namespace App\Services\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ExternalClient
{
    public function request(
        string $method,
        string $url,
        array $options = []
    ): Response {
        $client = $this->buildClient($options);

        return $client->send($method, $url, $options);
    }

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

    public function get(string $url, array $options = []): Response
    {
        return $this->request('GET', $url, $options);
    }

    public function post(string $url, array $options = []): Response
    {
        return $this->request('POST', $url, $options);
    }

    public function put(string $url, array $options = []): Response
    {
        return $this->request('PUT', $url, $options);
    }

    public function delete(string $url, array $options = []): Response
    {
        return $this->request('DELETE', $url, $options);
    }

    public function patch(string $url, array $options = []): Response
    {
        return $this->request('PATCH', $url, $options);
    }
}
