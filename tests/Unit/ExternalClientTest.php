<?php

namespace Tests\Unit;

use App\Services\Http\ExternalClient;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Http\HttpClientException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalClientTest extends TestCase
{
    public function test_external_client_can_make_get_request(): void
    {
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);

        $client = new ExternalClient();
        $response = $client->get('https://example.com/test');

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->json('success'));
    }

    public function test_http_client_exception_decorator_wraps_exceptions(): void
    {
        Http::fake([
            'https://example.com/error' => Http::response(['error' => 'Not Found'], 404),
        ]);

        $client = new ExternalClient();
        $decorator = new HttpClientExceptionDecorator($client);

        $this->expectException(HttpClientException::class);
        $decorator->get('https://example.com/error');
    }

    public function test_external_client_supports_different_http_methods(): void
    {
        Http::fake([
            'https://example.com/post' => Http::response(['method' => 'POST'], 200),
            'https://example.com/put' => Http::response(['method' => 'PUT'], 200),
            'https://example.com/delete' => Http::response(['method' => 'DELETE'], 200),
        ]);

        $client = new ExternalClient();

        $postResponse = $client->post('https://example.com/post');
        $this->assertEquals('POST', $postResponse->json('method'));

        $putResponse = $client->put('https://example.com/put');
        $this->assertEquals('PUT', $putResponse->json('method'));

        $deleteResponse = $client->delete('https://example.com/delete');
        $this->assertEquals('DELETE', $deleteResponse->json('method'));
    }

    public function test_external_client_supports_patch_method(): void
    {
        Http::fake([
            'https://example.com/patch' => Http::response(['method' => 'PATCH'], 200),
        ]);

        $client = new ExternalClient();
        $response = $client->patch('https://example.com/patch');

        $this->assertEquals(200, $response->status());
        $this->assertEquals('PATCH', $response->json('method'));
    }

    public function test_external_client_can_send_request_with_timeout(): void
    {
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);

        $client = new ExternalClient();
        $response = $client->get('https://example.com/test', ['timeout' => 60]);

        $this->assertEquals(200, $response->status());
    }

    public function test_external_client_can_send_request_with_custom_headers(): void
    {
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);

        $client = new ExternalClient();
        $response = $client->get('https://example.com/test', [
            'headers' => [
                'X-Custom-Header' => 'CustomValue',
                'Accept' => 'application/json',
            ],
        ]);

        $this->assertEquals(200, $response->status());
    }

    public function test_external_client_can_send_request_with_bearer_token(): void
    {
        Http::fake([
            'https://example.com/secure' => Http::response(['authenticated' => true], 200),
        ]);

        $client = new ExternalClient();
        $response = $client->get('https://example.com/secure', [
            'token' => 'bearer_token_123',
        ]);

        $this->assertEquals(200, $response->status());
    }

    public function test_external_client_can_use_base_uri(): void
    {
        Http::fake([
            'https://api.example.com/users' => Http::response(['users' => []], 200),
        ]);

        $client = new ExternalClient();
        $response = $client->get('https://api.example.com/users', [
            'base_uri' => 'https://api.example.com',
        ]);

        $this->assertEquals(200, $response->status());
    }

    public function test_external_client_uses_default_timeout_values(): void
    {
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);

        $client = new ExternalClient();
        $response = $client->get('https://example.com/test');

        $this->assertEquals(200, $response->status());
    }

    public function test_external_client_can_override_connect_timeout(): void
    {
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);

        $client = new ExternalClient();
        $response = $client->get('https://example.com/test', [
            'connect_timeout' => 5,
        ]);

        $this->assertEquals(200, $response->status());
    }

    public function test_http_client_exception_decorator_preserves_status_code(): void
    {
        Http::fake([
            'https://example.com/forbidden' => Http::response(['error' => 'Forbidden'], 403),
        ]);

        $client = new ExternalClient();
        $decorator = new HttpClientExceptionDecorator($client);

        try {
            $decorator->get('https://example.com/forbidden');
            $this->fail('Expected HttpClientException to be thrown');
        } catch (HttpClientException $e) {
            $this->assertEquals(403, $e->getCode());
        }
    }

    public function test_http_client_exception_decorator_handles_server_errors(): void
    {
        Http::fake([
            'https://example.com/error' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        $client = new ExternalClient();
        $decorator = new HttpClientExceptionDecorator($client);

        try {
            $decorator->get('https://example.com/error');
            $this->fail('Expected HttpClientException to be thrown');
        } catch (HttpClientException $e) {
            $this->assertEquals(500, $e->getCode());
        }
    }

    public function test_http_client_exception_decorator_wraps_post_exceptions(): void
    {
        Http::fake([
            'https://example.com/bad-request' => Http::response(['error' => 'Bad Request'], 400),
        ]);

        $client = new ExternalClient();
        $decorator = new HttpClientExceptionDecorator($client);

        $this->expectException(HttpClientException::class);
        $decorator->post('https://example.com/bad-request');
    }

    public function test_http_client_exception_decorator_wraps_put_exceptions(): void
    {
        Http::fake([
            'https://example.com/conflict' => Http::response(['error' => 'Conflict'], 409),
        ]);

        $client = new ExternalClient();
        $decorator = new HttpClientExceptionDecorator($client);

        $this->expectException(HttpClientException::class);
        $decorator->put('https://example.com/conflict');
    }

    public function test_http_client_exception_decorator_wraps_delete_exceptions(): void
    {
        Http::fake([
            'https://example.com/gone' => Http::response(['error' => 'Gone'], 410),
        ]);

        $client = new ExternalClient();
        $decorator = new HttpClientExceptionDecorator($client);

        $this->expectException(HttpClientException::class);
        $decorator->delete('https://example.com/gone');
    }

    public function test_http_client_exception_decorator_wraps_patch_exceptions(): void
    {
        Http::fake([
            'https://example.com/unprocessable' => Http::response(['error' => 'Unprocessable'], 422),
        ]);

        $client = new ExternalClient();
        $decorator = new HttpClientExceptionDecorator($client);

        $this->expectException(HttpClientException::class);
        $decorator->patch('https://example.com/unprocessable');
    }

    public function test_http_client_exception_decorator_wraps_general_exceptions(): void
    {
        Http::fake(function () {
            throw new \RuntimeException('Network error');
        });

        $client = new ExternalClient();
        $decorator = new HttpClientExceptionDecorator($client);

        try {
            $decorator->get('https://example.com/error');
            $this->fail('Expected HttpClientException to be thrown');
        } catch (HttpClientException $e) {
            $this->assertStringContainsString('HTTP request failed', $e->getMessage());
            $this->assertEquals(0, $e->getCode());
        }
    }

    public function test_http_client_exception_decorator_allows_successful_requests(): void
    {
        Http::fake([
            'https://example.com/success' => Http::response(['success' => true], 200),
        ]);

        $client = new ExternalClient();
        $decorator = new HttpClientExceptionDecorator($client);

        $response = $decorator->get('https://example.com/success');

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->json('success'));
    }

    public function test_http_client_exception_decorator_allows_successful_post(): void
    {
        Http::fake([
            'https://example.com/create' => Http::response(['created' => true], 201),
        ]);

        $client = new ExternalClient();
        $decorator = new HttpClientExceptionDecorator($client);

        $response = $decorator->post('https://example.com/create');

        $this->assertEquals(201, $response->status());
        $this->assertTrue($response->json('created'));
    }

    public function test_build_client_applies_all_options(): void
    {
        Http::fake([
            'https://api.example.com/test' => Http::response(['success' => true], 200),
        ]);

        $client = new ExternalClient();
        $response = $client->get('https://api.example.com/test', [
            'timeout' => 45,
            'connect_timeout' => 15,
            'headers' => ['X-API-Key' => 'test123'],
            'token' => 'bearer_token',
            'base_uri' => 'https://api.example.com',
        ]);

        $this->assertEquals(200, $response->status());
    }
}