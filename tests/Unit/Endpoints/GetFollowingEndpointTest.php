<?php

namespace Tests\Unit\Endpoints;

use App\Models\Account;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\Endpoints\GetFollowingEndpoint;
use App\Services\Instagram\InstagramApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

class GetFollowingEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeHttpClient $fakeHttpClient;
    private GetFollowingEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeHttpClient = new FakeHttpClient;
        $this->endpoint = new GetFollowingEndpoint(
            new InstagramApiClient(new HttpClientExceptionDecorator($this->fakeHttpClient))
        );
    }

    #[Test]
    public function it_returns_following_collection_from_api(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok1']);
        $this->fakeHttpClient->addResponse('/me/following', [
            'data' => [
                ['id' => 'u1', 'username' => 'friend_one'],
                ['id' => 'u2', 'username' => 'friend_two'],
            ],
        ]);

        /* Act */
        $result = $this->endpoint->execute($account);

        /* Assert */
        $this->assertCount(2, $result);
        $this->assertSame('friend_one', $result->first()['username']);
        $this->assertSame('tok1', $this->fakeHttpClient->getRequestHistory()[0]['options']['token']);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_following(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok1']);
        $this->fakeHttpClient->addResponse('/me/following', ['data' => []]);

        /* Act */
        $result = $this->endpoint->execute($account);

        /* Assert */
        $this->assertCount(0, $result);
    }

    #[Test]
    public function it_throws_exception_when_account_has_no_access_token(): void
    {
        /* Arrange */
        $account = Account::factory()->withoutAccessToken()->create();

        /* Act */
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No access token available');
        $this->endpoint->execute($account);

        /* Assert */
    }
}
