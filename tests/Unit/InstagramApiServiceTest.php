<?php

namespace Tests\Unit;

use App\Models\InstagramAccount;
use App\Services\Http\HttpClientException;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Http\Client\Response;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class InstagramApiServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_throws_exception_when_getting_stories_without_access_token(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act & Assert */
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No access token available for account: test');
        $service->getStories($account);
        /** #endregion */
    }

    #[Test]
    public function it_returns_collection_of_stories(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([
                ['id' => 'story1', 'media_url' => 'https://example.com/1.jpg'],
                ['id' => 'story2', 'media_url' => 'https://example.com/2.jpg'],
            ]);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://graph.instagram.com/me/stories', [
                'base_uri' => 'https://graph.instagram.com',
                'token' => 'valid_token',
            ])
            ->andReturn($mockResponse);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $stories = $service->getStories($account);
        /** #endregion */

        /** #region Assert */
        $this->assertCount(2, $stories);
        $this->assertEquals('story1', $stories->first()['id']);
        /** #endregion */
    }

    #[Test]
    public function it_returns_empty_collection_when_no_stories_data(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([]);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn($mockResponse);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $stories = $service->getStories($account);
        /** #endregion */

        /** #region Assert */
        $this->assertCount(0, $stories);
        /** #endregion */
    }

    #[Test]
    public function it_throws_exception_when_getting_story_comments_without_access_token(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act & Assert */
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No access token available for account: test');
        $service->getStoryComments($account, 'story123');
        /** #endregion */
    }

    #[Test]
    public function it_returns_collection_of_story_comments(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([
                ['id' => 'comment1', 'text' => 'Great story!'],
                ['id' => 'comment2', 'text' => 'Love it!'],
            ]);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://graph.instagram.com/story123/comments', [
                'base_uri' => 'https://graph.instagram.com',
                'token' => 'valid_token',
            ])
            ->andReturn($mockResponse);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $comments = $service->getStoryComments($account, 'story123');
        /** #endregion */

        /** #region Assert */
        $this->assertCount(2, $comments);
        $this->assertEquals('comment1', $comments->first()['id']);
        /** #endregion */
    }

    #[Test]
    public function it_returns_empty_collection_when_no_story_comments(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([]);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn($mockResponse);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $comments = $service->getStoryComments($account, 'story123');
        /** #endregion */

        /** #region Assert */
        $this->assertCount(0, $comments);
        /** #endregion */
    }

    #[Test]
    public function it_returns_false_when_blocking_user_without_access_token(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $result = $service->blockUser($account, 'user123');
        /** #endregion */

        /** #region Assert */
        $this->assertFalse($result);
        /** #endregion */
    }

    #[Test]
    public function it_returns_true_when_blocking_user_successfully(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockResponse = Mockery::mock(Response::class);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('POST', 'https://graph.instagram.com/me/blocked', [
                'base_uri' => 'https://graph.instagram.com',
                'token' => 'valid_token',
                'json' => ['user_id' => 'user123'],
            ])
            ->andReturn($mockResponse);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $result = $service->blockUser($account, 'user123');
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($result);
        /** #endregion */
    }

    #[Test]
    public function it_returns_false_when_blocking_user_throws_http_exception(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andThrow(new HttpClientException('API Error', 500));
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $result = $service->blockUser($account, 'user123');
        /** #endregion */

        /** #region Assert */
        $this->assertFalse($result);
        /** #endregion */
    }

    #[Test]
    public function it_returns_false_when_blocking_user_throws_general_exception(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andThrow(new RuntimeException('Network error'));
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $result = $service->blockUser($account, 'user123');
        /** #endregion */

        /** #region Assert */
        $this->assertFalse($result);
        /** #endregion */
    }

    #[Test]
    public function it_returns_null_when_getting_user_info_without_access_token(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $userInfo = $service->getUserInfo($account, 'search_user');
        /** #endregion */

        /** #region Assert */
        $this->assertNull($userInfo);
        /** #endregion */
    }

    #[Test]
    public function it_returns_first_user_from_search_results(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([
                ['id' => 'user123', 'username' => 'search_user'],
                ['id' => 'user456', 'username' => 'another_user'],
            ]);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('GET', 'https://graph.instagram.com/search', [
                'base_uri' => 'https://graph.instagram.com',
                'token' => 'valid_token',
                'query' => [
                    'q' => 'search_user',
                    'type' => 'user',
                ],
            ])
            ->andReturn($mockResponse);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $userInfo = $service->getUserInfo($account, 'search_user');
        /** #endregion */

        /** #region Assert */
        $this->assertIsArray($userInfo);
        $this->assertEquals('user123', $userInfo['id']);
        $this->assertEquals('search_user', $userInfo['username']);
        /** #endregion */
    }

    #[Test]
    public function it_returns_null_when_user_search_has_no_results(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([]);
        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn($mockResponse);
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $userInfo = $service->getUserInfo($account, 'nonexistent_user');
        /** #endregion */

        /** #region Assert */
        $this->assertNull($userInfo);
        /** #endregion */
    }

    #[Test]
    public function it_returns_null_when_getting_user_info_throws_http_exception(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andThrow(new HttpClientException('API Error', 500));
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $userInfo = $service->getUserInfo($account, 'search_user');
        /** #endregion */

        /** #region Assert */
        $this->assertNull($userInfo);
        /** #endregion */
    }

    #[Test]
    public function it_returns_null_when_getting_user_info_throws_general_exception(): void
    {
        /** #region Arrange */
        $account = new InstagramAccount(['username' => 'test']);
        $account->access_token = 'valid_token';

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andThrow(new RuntimeException('Network timeout'));
        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $userInfo = $service->getUserInfo($account, 'search_user');
        /** #endregion */

        /** #region Assert */
        $this->assertNull($userInfo);
        /** #endregion */
    }
}
