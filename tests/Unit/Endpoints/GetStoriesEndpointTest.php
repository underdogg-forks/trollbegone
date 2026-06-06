<?php

namespace Tests\Unit\Endpoints;

use App\Models\Account;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\Endpoints\GetStoriesEndpoint;
use App\Services\Instagram\InstagramApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

class GetStoriesEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeHttpClient $fakeHttpClient;
    private GetStoriesEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeHttpClient = new FakeHttpClient;
        $this->endpoint = new GetStoriesEndpoint(
            new InstagramApiClient(new HttpClientExceptionDecorator($this->fakeHttpClient))
        );
    }

    #[Test]
    public function it_returns_stories_collection_from_api(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'story_token']);
        $this->fakeHttpClient->addResponse('/me/stories', [
            'data' => [
                ['id' => 's1', 'media_type' => 'IMAGE'],
                ['id' => 's2', 'media_type' => 'VIDEO'],
            ],
        ]);

        /* Act */
        $result = $this->endpoint->execute($account);

        /* Assert */
        $this->assertCount(2, $result);
        $this->assertSame('s1', $result->first()['id']);
        $this->assertSame('story_token', $this->fakeHttpClient->getRequestHistory()[0]['options']['token']);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_stories(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/me/stories', ['data' => []]);

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
        $this->endpoint->execute($account);

        /* Assert */
    }
}
