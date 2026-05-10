<?php

namespace Tests\Unit;

use App\Services\Http\ExternalClient;
use App\Services\Http\HttpClientException;
use App\Services\Http\HttpClientExceptionDecorator;
use Exception;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

class HttpClientExceptionDecoratorTest extends TestCase
{
    #[Test]
    public function it_preserves_original_exception_message(): void
    {        /* Arrange */
        Http::fake([
            'https://example.com/error' => Http::response(['message' => 'Resource not found'], 404),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        try {
            $decorator->get('https://example.com/error');
            $this->fail('Expected HttpClientException to be thrown');
            

            /** #endregion */

            /** #region Act */
            /* Act */
            

            /** #endregion */

            /** #region Assert */
            /* Assert */
            
        } catch (HttpClientException $e) {
            $this->assertNotEmpty($e->getMessage());
        /** #endregion */
        }
    }

    #[Test]
    public function it_chains_previous_exception(): void
    {        /* Arrange */
        Http::fake([
            'https://example.com/error' => Http::response(['error' => 'Unauthorized'], 401),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        try {
            $decorator->get('https://example.com/error');
            $this->fail('Expected HttpClientException to be thrown');
            

            /** #endregion */

            /** #region Act */
            /* Act */
            

            /** #endregion */

            /** #region Assert */
            /* Assert */
            
        } catch (HttpClientException $e) {
            $this->assertInstanceOf(Exception::class, $e->getPrevious());
        /** #endregion */
        }
    }

    #[Test]
    public function it_handles_network_timeout_exceptions(): void
    {        /* Arrange */
        $fakeClient = new FakeHttpClient;
        $fakeClient->addException('https://example.com/timeout', new Exception('Connection timeout'));

        $decorator = new HttpClientExceptionDecorator($fakeClient);
        try {
            $decorator->get('https://example.com/timeout');
            $this->fail('Expected HttpClientException to be thrown');
            

            /** #endregion */

            /** #region Act */
            /* Act */
            

            /** #endregion */

            /** #region Assert */
            /* Assert */
            
        } catch (HttpClientException $e) {
            $this->assertStringContainsString('HTTP request failed', $e->getMessage());
        /** #endregion */
        }
    }

    #[Test]
    public function it_handles_dns_resolution_failures(): void
    {        /* Arrange */
        $fakeClient = new FakeHttpClient;
        $fakeClient->addException('https://nonexistent.example.com/test', new Exception('Could not resolve host'));

        $decorator = new HttpClientExceptionDecorator($fakeClient);
        try {
            $decorator->get('https://nonexistent.example.com/test');
            $this->fail('Expected HttpClientException to be thrown');
            

            /** #endregion */

            /** #region Act */
            /* Act */
            

            /** #endregion */

            /** #region Assert */
            /* Assert */
            
        } catch (HttpClientException $e) {
            $this->assertEquals(0, $e->getCode());
        /** #endregion */
        }
    }

    #[Test]
    public function it_passes_through_200_responses(): void
    {        /* Arrange */
        Http::fake([
            'https://example.com/ok' => Http::response(['status' => 'ok'], 200),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        

        /** #endregion */

        /** #region Act */
        /* Act */
        $response = $decorator->get('https://example.com/ok');
        

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertEquals(200, $response->status());
        $this->assertEquals('ok', $response->json('status'));
        
    /** #endregion */
    }

    #[Test]
    public function it_passes_through_201_created_responses(): void
    {        /* Arrange */
        Http::fake([
            'https://example.com/created' => Http::response(['id' => 123], 201),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        

        /** #endregion */

        /** #region Act */
        /* Act */
        $response = $decorator->post('https://example.com/created');
        

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertEquals(201, $response->status());
        $this->assertEquals(123, $response->json('id'));
        
    /** #endregion */
    }

    #[Test]
    public function it_passes_through_204_no_content_responses(): void
    {        /* Arrange */
        Http::fake([
            'https://example.com/deleted' => Http::response(null, 204),
        ]);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        

        /** #endregion */

        /** #region Act */
        /* Act */
        $response = $decorator->delete('https://example.com/deleted');
        

        /** #endregion */

        /** #region Assert */
        /* Assert */
        $this->assertEquals(204, $response->status());
        
    /** #endregion */
    }

    #[Test]
    public function it_wraps_all_4xx_errors(): void
    {        /* Arrange */
        $statusCodes = [400, 401, 403, 404, 405, 409, 422, 429];
        foreach ($statusCodes as $statusCode) {
            Http::fake([
                "https://example.com/error-{$statusCode}" => Http::response(['error' => 'Error'], $statusCode),
            ]);
            $client = new ExternalClient;
            $decorator = new HttpClientExceptionDecorator($client);
            try {
                $decorator->get("https://example.com/error-{$statusCode}");
                $this->fail("Expected HttpClientException for status code {$statusCode}");
                

                /** #endregion */

                /** #region Act */
                /* Act */
                

                /** #endregion */

                /** #region Assert */
                /* Assert */
                
            } catch (HttpClientException $e) {
                $this->assertEquals($statusCode, $e->getCode());
            /** #endregion */
            }
        }
    }

    #[Test]
    public function it_wraps_all_5xx_errors(): void
    {        /* Arrange */
        $statusCodes = [500, 502, 503, 504];
        foreach ($statusCodes as $statusCode) {
            Http::fake([
                "https://example.com/error-{$statusCode}" => Http::response(['error' => 'Server Error'], $statusCode),
            ]);
            $client = new ExternalClient;
            $decorator = new HttpClientExceptionDecorator($client);
            try {
                $decorator->get("https://example.com/error-{$statusCode}");
                $this->fail("Expected HttpClientException for status code {$statusCode}");
                

                /** #endregion */

                /** #region Act */
                /* Act */
                

                /** #endregion */

                /** #region Assert */
                /* Assert */
                
            } catch (HttpClientException $e) {
                $this->assertEquals($statusCode, $e->getCode());
            /** #endregion */
            }
        }
    }

    #[Test]
    public function it_handles_missing_response_in_exception(): void
    {        /* Arrange */
        $fakeClient = new FakeHttpClient;
        $fakeClient->addException('https://example.com/error', new Exception('Request failed'));

        $decorator = new HttpClientExceptionDecorator($fakeClient);
        try {
            $decorator->get('https://example.com/error');
            $this->fail('Expected HttpClientException to be thrown');
            

            /** #endregion */

            /** #region Act */
            /* Act */
            

            /** #endregion */

            /** #region Assert */
            /* Assert */
            
        } catch (HttpClientException $e) {
            $this->assertEquals(0, $e->getCode());
        /** #endregion */
        }
    }
}
