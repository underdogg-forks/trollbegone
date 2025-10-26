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
}
