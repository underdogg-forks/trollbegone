<?php

namespace Tests\Unit\Endpoints;

use App\Models\Account;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\Endpoints\BlockUserEndpoint;
use App\Services\Instagram\InstagramApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

class BlockUserEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeHttpClient $fakeHttpClient;
    private BlockUserEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeHttpClient = new FakeHttpClient;
        $this->endpoint = new BlockUserEndpoint(
            new InstagramApiClient(new HttpClientExceptionDecorator($this->fakeHttpClient))
        );
    }

    #[Test]
    public function it_returns_true_on_successful_block(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/me/blocked', ['success' => true]);

        /* Act */
        $result = $this->endpoint->execute($account, 'user-id-123');

        /* Assert */
        $this->assertTrue($result);
        $request = $this->fakeHttpClient->getRequestHistory()[0];
        $this->assertSame('POST', $request['method']);
        $this->assertSame('tok', $request['options']['token']);
    }

    #[Test]
    public function it_throws_exception_on_api_error(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->throwExceptionOnNextRequest('API Error');

        /* Act */
        $this->expectException(\Exception::class);
        $this->endpoint->execute($account, 'user-id-123');

        /* Assert */
    }

    #[Test]
    public function it_throws_exception_when_account_has_no_access_token(): void
    {
        /* Arrange */
        $account = Account::factory()->withoutAccessToken()->create();

        /* Act */
        $this->expectException(\Exception::class);
        $this->endpoint->execute($account, 'user-id-123');

        /* Assert */
    }
}
