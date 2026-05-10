<?php

namespace Tests\Unit;

use App\Services\Http\RequestLoggerDecorator;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

class RequestLoggerDecoratorTest extends TestCase
{
    #[Test]
    public function it_logs_successful_requests(): void
    {
        /** #region Arrange */
        Log::spy();
        $client = new FakeHttpClient;
        $client->addResponse('https://example.com/test', ['ok' => true]);
        $decorator = new RequestLoggerDecorator($client);
        /** #endregion */

        /** #region Act */
        $decorator->request('GET', 'https://example.com/test');
        /** #endregion */

        /** #region Assert */
        Log::shouldHaveReceived('info')->once();
        /** #endregion */
    }

    #[Test]
    public function it_logs_failed_requests_and_rethrows(): void
    {
        /** #region Arrange */
        Log::spy();
        $client = new FakeHttpClient;
        $client->addException('https://example.com/test', new \RuntimeException('boom'));
        $decorator = new RequestLoggerDecorator($client);
        /** #endregion */

        /** #region Act */
        try {
            $decorator->request('GET', 'https://example.com/test');
            $this->fail('Expected RuntimeException to be thrown');
        } catch (\RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }
        /** #endregion */

        /** #region Assert */
        Log::shouldHaveReceived('warning')->once();
        /** #endregion */
    }
}
