<?php

namespace Tests\Unit\Endpoints;

use App\Models\Account;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\Endpoints\GetStoryCommentsEndpoint;
use App\Services\Instagram\InstagramApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

class GetStoryCommentsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeHttpClient $fakeHttpClient;
    private GetStoryCommentsEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeHttpClient = new FakeHttpClient;
        $this->endpoint = new GetStoryCommentsEndpoint(
            new InstagramApiClient(new HttpClientExceptionDecorator($this->fakeHttpClient))
        );
    }

    #[Test]
    public function it_returns_comments_for_a_story(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/story-42/comments', [
            'data' => [
                ['id' => 'c1', 'text' => 'Nice!'],
                ['id' => 'c2', 'text' => 'Love it!'],
            ],
        ]);

        /* Act */
        $result = $this->endpoint->execute($account, 'story-42');

        /* Assert */
        $this->assertCount(2, $result);
        $this->assertSame('c1', $result->first()['id']);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_comments(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/story-42/comments', ['data' => []]);

        /* Act */
        $result = $this->endpoint->execute($account, 'story-42');

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
        $this->endpoint->execute($account, 'story-42');

        /* Assert */
    }
}
