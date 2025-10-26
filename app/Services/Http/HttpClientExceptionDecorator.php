<?php

namespace App\Services\Http;

use BadMethodCallException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

/**
 * HttpClientExceptionDecorator wraps ExternalClient to provide consistent exception handling.
 * All HTTP errors and exceptions are caught and wrapped in HttpClientException.
 */
class HttpClientExceptionDecorator
{
    public function __construct(
        protected ExternalClient $client
    ) {}

    /**
     * Send an HTTP request with exception handling.
     *
     * @param  string  $method  HTTP method
     * @param  string  $url  Request URL
     * @param  array  $options  Request options
     *
     * @throws HttpClientException
     */
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

    /**
     * Magic method to handle HTTP method calls (get, post, put, delete, patch).
     * This allows backward compatibility with method-specific calls.
     *
     * @param  string  $method  The HTTP method name (get, post, put, delete, patch)
     * @param  array  $arguments  Arguments passed to the method [url, options]
     *
     * @throws HttpClientException
     */
    public function __call(string $method, array $arguments): Response
    {
        $allowedMethods = ['get', 'post', 'put', 'delete', 'patch', 'head', 'options'];

        if (! in_array(strtolower($method), $allowedMethods)) {
            throw new BadMethodCallException("Method {$method} is not supported");
        }

        $url = $arguments[0] ?? '';
        $options = $arguments[1] ?? [];

        return $this->request(strtoupper($method), $url, $options);
    }
}
