<?php

namespace Tests\Unit\Endpoints;

use App\Models\Account;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\Endpoints\GetUserInfoEndpoint;
use App\Services\Instagram\InstagramApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

class GetUserInfoEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeHttpClient $fakeHttpClient;
    private GetUserInfoEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeHttpClient = new FakeHttpClient;
        $this->endpoint = new GetUserInfoEndpoint(
            new InstagramApiClient(new HttpClientExceptionDecorator($this->fakeHttpClient))
        );
    }

    #[Test]
    public function it_returns_first_user_from_search_results(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/search', [
            'data' => [
                ['id' => 'u123', 'username' => 'found_user'],
                ['id' => 'u456', 'username' => 'other_user'],
            ],
        ]);

        /* Act */
        $result = $this->endpoint->execute($account, 'found_user');

        /* Assert */
        $this->assertIsArray($result);
        $this->assertSame('u123', $result['id']);
        $this->assertSame('found_user', $result['username']);
    }

    #[Test]
    public function it_returns_null_when_user_not_found(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/search', ['data' => []]);

        /* Act */
        $result = $this->endpoint->execute($account, 'ghost_user');

        /* Assert */
        $this->assertNull($result);
    }

    #[Test]
    public function it_throws_exception_when_account_has_no_access_token(): void
    {
        /* Arrange */
        $account = Account::factory()->withoutAccessToken()->create();

        /* Act */
        $this->expectException(\Exception::class);
        $this->endpoint->execute($account, 'any_user');

        /* Assert */
    }
}
