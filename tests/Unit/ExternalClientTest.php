<?php

namespace Tests\Unit;

use App\Services\Http\ExternalClient;
use App\Services\Http\HttpClientException;
use App\Services\Http\HttpClientExceptionDecorator;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExternalClientTest extends TestCase
{
    #[Test]
    public function can_make_get_request(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);
        $client = new ExternalClient;
        /** #endregion */

        /** #region Act */
        $response = $client->request('GET', 'https://example.com/test');
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->json('success'));
        /** #endregion */
    }

    #[Test]
    public function exception_decorator_wraps_exceptions(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/error' => Http::response(['error' => 'Not Found'], 404),
        ]);
        $client = new ExternalClient;
        /** #endregion */

        /** #region Act */
        $decorator = new HttpClientExceptionDecorator($client);
        /** #endregion */

        /** #region Assert */
        $this->expectException(HttpClientException::class);
        $decorator->request('GET', 'https://example.com/error');
        /** #endregion */
    }

    #[Test]
    public function supports_different_http_methods(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/post' => Http::response(['method' => 'POST'], 200),
            'https://example.com/put' => Http::response(['method' => 'PUT'], 200),
            'https://example.com/delete' => Http::response(['method' => 'DELETE'], 200),
        ]);
        $client = new ExternalClient;
        $postResponse = $client->request('POST', 'https://example.com/post');
        /** #endregion */

        /** #region Act */
        $putResponse = $client->request('PUT', 'https://example.com/put');
        $deleteResponse = $client->request('DELETE', 'https://example.com/delete');
        /** #endregion */

        /** #region Assert */
        $this->assertEquals('POST', $postResponse->json('method'));
        $this->assertEquals('PUT', $putResponse->json('method'));
        $this->assertEquals('DELETE', $deleteResponse->json('method'));
        /** #endregion */
    }

    #[Test]
    public function supports_patch_method(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/patch' => Http::response(['method' => 'PATCH'], 200),
        ]);
        $client = new ExternalClient;
        /** #endregion */

        /** #region Act */
        $response = $client->request('PATCH', 'https://example.com/patch');
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        $this->assertEquals('PATCH', $response->json('method'));
        /** #endregion */
    }

    #[Test]
    public function can_send_request_with_timeout(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);
        $client = new ExternalClient;
        /** #endregion */

        /** #region Act */
        $response = $client->request('GET', 'https://example.com/test', ['timeout' => 60]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        /** #endregion */
    }

    #[Test]
    public function can_send_request_with_custom_headers(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);
        $client = new ExternalClient;
        $response = $client->request('GET', 'https://example.com/test', [
            'headers' => [
                'X-Custom-Header' => 'CustomValue',
                'Accept' => 'application/json',
            ],
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        /** #endregion */
    }

    #[Test]
    public function can_send_request_with_bearer_token(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/secure' => Http::response(['authenticated' => true], 200),
        ]);
        $client = new ExternalClient;
        $response = $client->request('GET', 'https://example.com/secure', [
            'token' => 'bearer_token_123',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        /** #endregion */
    }

    #[Test]
    public function can_use_base_uri(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://api.example.com/users' => Http::response(['users' => []], 200),
        ]);
        $client = new ExternalClient;
        $response = $client->request('GET', 'https://api.example.com/users', [
            'base_uri' => 'https://api.example.com',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        /** #endregion */
    }

    #[Test]
    public function uses_default_timeout_values(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);
        $client = new ExternalClient;
        /** #endregion */

        /** #region Act */
        $response = $client->request('GET', 'https://example.com/test');
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        /** #endregion */
    }

    #[Test]
    public function can_override_connect_timeout(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);
        $client = new ExternalClient;
        $response = $client->request('GET', 'https://example.com/test', [
            'connect_timeout' => 5,
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        /** #endregion */
    }

    #[Test]
    public function exception_decorator_preserves_status_code(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/forbidden' => Http::response(['error' => 'Forbidden'], 403),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        try {
            $decorator->request('GET', 'https://example.com/forbidden');
            $this->fail('Expected HttpClientException to be thrown');
            /** #endregion */

            /** #region Act */
            // No action needed
            /** #endregion */

            /** #region Assert */
            // No assertions
            /** #endregion */
        } catch (HttpClientException $e) {
            $this->assertEquals(403, $e->getCode());
        }
    }

    #[Test]
    public function exception_decorator_handles_server_errors(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/error' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        try {
            $decorator->request('GET', 'https://example.com/error');
            $this->fail('Expected HttpClientException to be thrown');
            /** #endregion */

            /** #region Act */
            // No action needed
            /** #endregion */

            /** #region Assert */
            // No assertions
            /** #endregion */
        } catch (HttpClientException $e) {
            $this->assertEquals(500, $e->getCode());
        }
    }

    #[Test]
    public function exception_decorator_wraps_post_exceptions(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/bad-request' => Http::response(['error' => 'Bad Request'], 400),
        ]);
        $client = new ExternalClient;
        /** #endregion */

        /** #region Act */
        $decorator = new HttpClientExceptionDecorator($client);
        /** #endregion */

        /** #region Assert */
        $this->expectException(HttpClientException::class);
        $decorator->request('POST', 'https://example.com/bad-request');
        /** #endregion */
    }

    #[Test]
    public function exception_decorator_wraps_put_exceptions(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/conflict' => Http::response(['error' => 'Conflict'], 409),
        ]);
        $client = new ExternalClient;
        /** #endregion */

        /** #region Act */
        $decorator = new HttpClientExceptionDecorator($client);
        /** #endregion */

        /** #region Assert */
        $this->expectException(HttpClientException::class);
        $decorator->request('PUT', 'https://example.com/conflict');
        /** #endregion */
    }

    #[Test]
    public function exception_decorator_wraps_delete_exceptions(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/gone' => Http::response(['error' => 'Gone'], 410),
        ]);
        $client = new ExternalClient;
        /** #endregion */

        /** #region Act */
        $decorator = new HttpClientExceptionDecorator($client);
        /** #endregion */

        /** #region Assert */
        $this->expectException(HttpClientException::class);
        $decorator->request('DELETE', 'https://example.com/gone');
        /** #endregion */
    }

    #[Test]
    public function exception_decorator_wraps_patch_exceptions(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/unprocessable' => Http::response(['error' => 'Unprocessable'], 422),
        ]);
        $client = new ExternalClient;
        /** #endregion */

        /** #region Act */
        $decorator = new HttpClientExceptionDecorator($client);
        /** #endregion */

        /** #region Assert */
        $this->expectException(HttpClientException::class);
        $decorator->request('PATCH', 'https://example.com/unprocessable');
        /** #endregion */
    }

    #[Test]
    public function exception_decorator_wraps_general_exceptions(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake(function () {
            throw new \RuntimeException('Network error');
            /** #endregion */

            /** #region Act */
            // No action needed
            /** #endregion */

            /** #region Assert */
            // No assertions
            /** #endregion */
        });

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);

        try {
            $decorator->request('GET', 'https://example.com/error');
            $this->fail('Expected HttpClientException to be thrown');
        } catch (HttpClientException $e) {
            $this->assertStringContainsString('HTTP request failed', $e->getMessage());
            $this->assertEquals(0, $e->getCode());
        }
    }

    #[Test]
    public function exception_decorator_allows_successful_requests(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/success' => Http::response(['success' => true], 200),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        /** #endregion */

        /** #region Act */
        $response = $decorator->request('GET', 'https://example.com/success');
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->json('success'));
        /** #endregion */
    }

    #[Test]
    public function exception_decorator_allows_successful_post(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/create' => Http::response(['created' => true], 201),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        /** #endregion */

        /** #region Act */
        $response = $decorator->request('POST', 'https://example.com/create');
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(201, $response->status());
        $this->assertTrue($response->json('created'));
        /** #endregion */
    }

    #[Test]
    public function applies_all_options(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://api.example.com/test' => Http::response(['success' => true], 200),
        ]);
        $client = new ExternalClient;
        $response = $client->request('GET', 'https://api.example.com/test', [
            'timeout' => 45,
            'connect_timeout' => 15,
            'headers' => ['X-API-Key' => 'test123'],
            'token' => 'bearer_token',
            'base_uri' => 'https://api.example.com',
        /** #endregion */

        /** #region Act */
        ]);
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        /** #endregion */
    }

    #[Test]
    public function call_method_works_for_get(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/test' => Http::response(['success' => true], 200),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        /** #endregion */

        /** #region Act */
        $response = $decorator->get('https://example.com/test');
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->json('success'));
        /** #endregion */
    }

    #[Test]
    public function call_method_works_for_post(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        Http::fake([
            'https://example.com/create' => Http::response(['created' => true], 201),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        /** #endregion */

        /** #region Act */
        $response = $decorator->post('https://example.com/create');
        /** #endregion */

        /** #region Assert */
        $this->assertEquals(201, $response->status());
        $this->assertTrue($response->json('created'));
        /** #endregion */
    }

    #[Test]
    public function call_method_throws_for_invalid_method(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $client = new ExternalClient;
        /** #endregion */

        /** #region Act */
        $decorator = new HttpClientExceptionDecorator($client);
        /** #endregion */

        /** #region Assert */
        $this->expectException(\BadMethodCallException::class);
        $decorator->invalid('https://example.com/test');
        /** #endregion */
    }
}
