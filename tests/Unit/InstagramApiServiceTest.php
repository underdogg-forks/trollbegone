<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\InstagramAccount;
use App\Services\Http\HttpClientException;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery;
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
    public function it_get_stories_throws_exception_when_no_access_token(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount(['username' => 'test']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $service = new InstagramApiService($mockClient);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No access token available for account: test');

        $service->getStories($account);
    }

    #[Test]
    public function it_get_stories_returns_collection_of_stories(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([
                ['id' => 'story1', 'media_url' => 'https://example.com/1.jpg'],
                ['id' => 'story2', 'media_url' => 'https://example.com/2.jpg'],
            ]);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->with('https://graph.instagram.com/me/stories', [
                'base_uri' => 'https://graph.instagram.com',
                'token' => 'valid_token',
            ])
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $stories = $service->getStories($account);

        $this->assertCount(2, $stories);
        $this->assertEquals('story1', $stories->first()['id']);
    }

    #[Test]
    public function it_get_stories_returns_empty_collection_when_no_data(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([]);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $stories = $service->getStories($account);

        $this->assertCount(0, $stories);
    }

    #[Test]
    public function it_get_story_comments_throws_exception_when_no_access_token(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount(['username' => 'test']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $service = new InstagramApiService($mockClient);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No access token available for account: test');

        $service->getStoryComments($account, 'story123');
    }

    #[Test]
    public function it_get_story_comments_returns_collection_of_comments(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([
                ['id' => 'comment1', 'text' => 'Great story!'],
                ['id' => 'comment2', 'text' => 'Love it!'],
            ]);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->with('https://graph.instagram.com/story123/comments', [
                'base_uri' => 'https://graph.instagram.com',
                'token' => 'valid_token',
            ])
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $comments = $service->getStoryComments($account, 'story123');

        $this->assertCount(2, $comments);
        $this->assertEquals('comment1', $comments->first()['id']);
    }

    #[Test]
    public function it_get_story_comments_returns_empty_collection_when_no_comments(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([]);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $comments = $service->getStoryComments($account, 'story123');

        $this->assertCount(0, $comments);
    }

    #[Test]
    public function it_block_user_returns_false_when_no_access_token(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount(['username' => 'test']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $service = new InstagramApiService($mockClient);

        $result = $service->blockUser($account, 'user123');
        
        $this->assertFalse($result);
    }

    #[Test]
    public function it_block_user_returns_true_on_success(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockResponse = Mockery::mock(Response::class);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('post')
            ->once()
            ->with('https://graph.instagram.com/me/blocked', [
                'base_uri' => 'https://graph.instagram.com',
                'token' => 'valid_token',
                'json' => ['user_id' => 'user123'],
            ])
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $result = $service->blockUser($account, 'user123');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_block_user_returns_false_on_exception(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('post')
            ->once()
            ->andThrow(new HttpClientException('API Error', 500));

        $service = new InstagramApiService($mockClient);
        $result = $service->blockUser($account, 'user123');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_block_user_returns_false_on_general_exception(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('post')
            ->once()
            ->andThrow(new RuntimeException('Network error'));

        $service = new InstagramApiService($mockClient);
        $result = $service->blockUser($account, 'user123');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_get_user_info_returns_null_when_no_access_token(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount(['username' => 'test']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $service = new InstagramApiService($mockClient);

        $userInfo = $service->getUserInfo($account, 'search_user');
        
        $this->assertNull($userInfo);
    }

    #[Test]
    public function it_get_user_info_returns_first_user_from_search_results(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([
                ['id' => 'user123', 'username' => 'search_user'],
                ['id' => 'user456', 'username' => 'another_user'],
            ]);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->with('https://graph.instagram.com/search', [
                'base_uri' => 'https://graph.instagram.com',
                'token' => 'valid_token',
                'query' => [
                    'q' => 'search_user',
                    'type' => 'user',
                ],
            ])
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $userInfo = $service->getUserInfo($account, 'search_user');

        $this->assertIsArray($userInfo);
        $this->assertEquals('user123', $userInfo['id']);
        $this->assertEquals('search_user', $userInfo['username']);
    }

    #[Test]
    public function it_get_user_info_returns_null_when_no_results(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([]);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $userInfo = $service->getUserInfo($account, 'nonexistent_user');

        $this->assertNull($userInfo);
    }

    #[Test]
    public function it_get_user_info_returns_null_on_exception(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andThrow(new HttpClientException('API Error', 500));

        $service = new InstagramApiService($mockClient);
        $userInfo = $service->getUserInfo($account, 'search_user');

        $this->assertNull($userInfo);
    }

    #[Test]
    public function it_get_user_info_returns_null_on_general_exception(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test',
            'access_token' => 'valid_token',
        ]);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andThrow(new RuntimeException('Network timeout'));

        $service = new InstagramApiService($mockClient);
        $userInfo = $service->getUserInfo($account, 'search_user');

        $this->assertNull($userInfo);
    }
}