<?php

namespace App\Services\Http;

use App\Enums\RequestMethod;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * RequestLoggerDecorator logs outbound API requests and responses.
 */
class RequestLoggerDecorator implements ApiClient
{
    public function __construct(
        protected ApiClient $client
    ) {}

    /**
     * Send a request and log request/response metadata.
     */
    public function request(
        RequestMethod|string $method,
        string $url,
        array $options = []
    ): Response {
        $methodValue = $method instanceof RequestMethod ? $method->value : (string) $method;
        $startedAt = microtime(true);

        try {
            $response = $this->client->request($method, $url, $options);

            Log::info('External API request completed', [
                'method' => $methodValue,
                'url' => $url,
                'status' => $response->status(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

            return $response;
        } catch (\Throwable $exception) {
            Log::warning('External API request failed', [
                'method' => $methodValue,
                'url' => $url,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
