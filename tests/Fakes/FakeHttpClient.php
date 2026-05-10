<?php

namespace Tests\Fakes;

use App\Enums\RequestMethod;
use App\Services\Http\ExternalClient;
use App\Services\Http\HttpClientException;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;

/**
 * FakeHttpClient provides a fake implementation of ExternalClient for testing.
 * This allows tests to define predictable responses without needing Mockery.
 */
class FakeHttpClient extends ExternalClient
{
    /**
     * @var array<string, array{response?: array, status?: int, exception?: \Exception}>
     */
    protected array $responses = [];

    /**
     * @var array<int, array{method: string, url: string, options: array}>
     */
    protected array $requestHistory = [];

    /**
     * Add a response for a specific URL pattern.
     *
     * @param  string  $urlPattern  URL or pattern to match
     * @param  array  $response  Response data to return
     * @param  int  $status  HTTP status code
     */
    public function addResponse(string $urlPattern, array $response, int $status = 200): void
    {
        $this->responses[$urlPattern] = [
            'response' => $response,
            'status' => $status,
        ];
    }

    /**
     * Add an exception to throw for a specific URL pattern.
     *
     * @param  string  $urlPattern  URL or pattern to match
     * @param  \Exception  $exception  Exception to throw
     */
    public function addException(string $urlPattern, \Exception $exception): void
    {
        $this->responses[$urlPattern] = [
            'exception' => $exception,
        ];
    }

    /**
     * Send an HTTP request with fake response handling.
     *
     * @param  RequestMethod|string  $method  HTTP method
     * @param  string  $url  Request URL
     * @param  array  $options  Request options
     *
     * @throws HttpClientException
     */
    public function request(
        RequestMethod|string $method,
        string $url,
        array $options = []
    ): Response {
        $methodValue = $method instanceof RequestMethod ? $method->value : $method;

        // Record the request
        $this->requestHistory[] = [
            'method' => $methodValue,
            'url' => $url,
            'options' => $options,
        ];

        // Find matching response
        $responseData = $this->findResponse($url);

        // Throw exception if configured
        if (isset($responseData['exception'])) {
            throw $responseData['exception'];
        }

        // Return fake response
        return new Response(new PsrResponse(
            $responseData['status'] ?? 200,
            ['Content-Type' => 'application/json'],
            json_encode($responseData['response'] ?? []) ?: '{}'
        ));
    }

    /**
     * Find a response for the given URL.
     */
    protected function findResponse(string $url): array
    {
        foreach ($this->responses as $pattern => $responseData) {
            // Exact match
            if ($url === $pattern) {
                return $responseData;
            }

            // Pattern match (contains)
            if (str_contains($url, $pattern)) {
                return $responseData;
            }
        }

        // Default successful response
        return ['response' => [], 'status' => 200];
    }

    /**
     * Get the request history.
     *
     * @return array<int, array{method: string, url: string, options: array}>
     */
    public function getRequestHistory(): array
    {
        return $this->requestHistory;
    }

    /**
     * Assert that a request was sent to a specific URL.
     */
    public function assertRequestSent(string $url): void
    {
        $found = false;
        foreach ($this->requestHistory as $request) {
            if (str_contains($request['url'], $url)) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that a request was sent to URL containing: {$url}"
            );
        }
    }

    /**
     * Reset the fake client state.
     */
    public function reset(): void
    {
        $this->responses = [];
        $this->requestHistory = [];
    }
}
