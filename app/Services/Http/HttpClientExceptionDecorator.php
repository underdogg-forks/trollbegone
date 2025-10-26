<?php

namespace App\Services\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;

class HttpClientExceptionDecorator
{
    public function __construct(
        protected ExternalClient $client
    ) {}

    public function request(
        string $method,
        string $url,
        array $options = []
    ): Response {
        try {
            $response = $this->client->request($method, $url, $options);
            
            $response->throw();
            
            return $response;
        } catch (RequestException $e) {
            throw new HttpClientException(
                message: $e->getMessage(),
                code: $e->response?->status() ?? 0,
                previous: $e
            );
        } catch (\Exception $e) {
            throw new HttpClientException(
                message: "HTTP request failed: {$e->getMessage()}",
                code: 0,
                previous: $e
            );
        }
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
