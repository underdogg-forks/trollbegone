<?php

namespace Tests\Unit\Endpoints;

use App\Models\Account;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\Endpoints\GetPostsByUsernameEndpoint;
use App\Services\Instagram\Endpoints\GetUserInfoEndpoint;
use App\Services\Instagram\InstagramApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

class GetPostsByUsernameEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeHttpClient $fakeHttpClient;
    private GetPostsByUsernameEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeHttpClient = new FakeHttpClient;
        $client = new InstagramApiClient(new HttpClientExceptionDecorator($this->fakeHttpClient));
        $this->endpoint = new GetPostsByUsernameEndpoint($client, new GetUserInfoEndpoint($client));
    }

    #[Test]
    public function it_returns_posts_for_an_existing_username(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/search', [
            'data' => [['id' => 'uid-1', 'username' => 'target_user']],
        ]);
        $this->fakeHttpClient->addResponse('/uid-1/media', [
            'data' => [
                ['id' => 'post-1', 'caption' => 'Hello world'],
                ['id' => 'post-2', 'caption' => 'Another post'],
            ],
        ]);

        /* Act */
        $result = $this->endpoint->execute($account, 'target_user');

        /* Assert */
        $this->assertCount(2, $result);
        $this->assertSame('post-1', $result->first()['id']);
    }

    #[Test]
    public function it_returns_empty_collection_when_username_not_found(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/search', ['data' => []]);

        /* Act */
        $result = $this->endpoint->execute($account, 'ghost_user');

        /* Assert */
        $this->assertCount(0, $result);
    }

    #[Test]
    public function it_returns_empty_collection_when_user_has_no_posts(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['access_token' => 'tok']);
        $this->fakeHttpClient->addResponse('/search', [
            'data' => [['id' => 'uid-1', 'username' => 'empty_user']],
        ]);
        $this->fakeHttpClient->addResponse('/uid-1/media', ['data' => []]);

        /* Act */
        $result = $this->endpoint->execute($account, 'empty_user');

        /* Assert */
        $this->assertCount(0, $result);
    }
}
