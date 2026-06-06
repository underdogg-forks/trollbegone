<?php

namespace Tests\Unit\Endpoints;

use App\Models\Account;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\Endpoints\GetPostCommentsEndpoint;
use App\Services\Instagram\InstagramApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

class GetPostCommentsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeHttpClient $fakeHttpClient;
    private GetPostCommentsEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeHttpClient = new FakeHttpClient;
        $this->endpoint = new GetPostCommentsEndpoint(
            new InstagramApiClient(new HttpClientExceptionDecorator($this->fakeHttpClient))
        );
    }

    #[Test]
    public function it_returns_comments_for_a_post(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/post-99/comments', [
            'data' => [
                ['id' => 'c1', 'username' => 'user_a', 'text' => 'Hello'],
                ['id' => 'c2', 'username' => 'user_b', 'text' => 'Spam!'],
            ],
        ]);

        /* Act */
        $result = $this->endpoint->execute($account, 'post-99');

        /* Assert */
        $this->assertCount(2, $result);
        $this->assertSame('user_a', $result->first()['username']);
        $this->assertSame('tok', $this->fakeHttpClient->getRequestHistory()[0]['options']['token']);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_comments(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/post-99/comments', ['data' => []]);

        /* Act */
        $result = $this->endpoint->execute($account, 'post-99');

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
        $this->endpoint->execute($account, 'post-99');

        /* Assert */
    }
}
