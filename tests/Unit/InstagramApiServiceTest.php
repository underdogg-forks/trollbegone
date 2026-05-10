<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Services\Http\ExternalClient;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstagramApiServiceTest extends TestCase
{
    #[Test]
    public function it_throws_exception_when_getting_stories_without_access_token(): void
    {
        /* Arrange */
        $account = new Account(['username' => 'test']);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /** #region Act & Assert */
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No access token available for account: test');
        $service->getStories($account);
        
    }

    #[Test]
    public function it_returns_collection_of_stories(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/me/stories' => Http::response([
                'data' => [
                    ['id' => 'story1', 'media_url' => 'https://example.com/1.jpg'],
                    ['id' => 'story2', 'media_url' => 'https://example.com/2.jpg'],
                ],
            ], 200),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $stories = $service->getStories($account);
        

        /* Assert */
        $this->assertCount(2, $stories);
        $this->assertEquals('story1', $stories->first()['id']);
        
    }

    #[Test]
    public function it_returns_empty_collection_when_no_stories_data(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/me/stories' => Http::response(['data' => []], 200),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $stories = $service->getStories($account);
        

        /* Assert */
        $this->assertCount(0, $stories);
        
    }

    #[Test]
    public function it_throws_exception_when_getting_story_comments_without_access_token(): void
    {
        /* Arrange */
        $account = new Account(['username' => 'test']);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /** #region Act & Assert */
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No access token available for account: test');
        $service->getStoryComments($account, 'story123');
        
    }

    #[Test]
    public function it_returns_collection_of_story_comments(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/story123/comments' => Http::response([
                'data' => [
                    ['id' => 'comment1', 'text' => 'Great story!'],
                    ['id' => 'comment2', 'text' => 'Love it!'],
                ],
            ], 200),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $comments = $service->getStoryComments($account, 'story123');
        

        /* Assert */
        $this->assertCount(2, $comments);
        $this->assertEquals('comment1', $comments->first()['id']);
        
    }

    #[Test]
    public function it_returns_empty_collection_when_no_story_comments(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/story123/comments' => Http::response(['data' => []], 200),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $comments = $service->getStoryComments($account, 'story123');
        

        /* Assert */
        $this->assertCount(0, $comments);
        
    }

    #[Test]
    public function it_returns_false_when_blocking_user_without_access_token(): void
    {
        /* Arrange */
        $account = new Account(['username' => 'test']);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $result = $service->blockUser($account, 'user123');
        

        /* Assert */
        $this->assertFalse($result);
        
    }

    #[Test]
    public function it_returns_true_when_blocking_user_successfully(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/me/blocked' => Http::response(['success' => true], 200),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $result = $service->blockUser($account, 'user123');
        

        /* Assert */
        $this->assertTrue($result);
        
    }

    #[Test]
    public function it_returns_false_when_blocking_user_throws_http_exception(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/me/blocked' => Http::response(['error' => 'API Error'], 500),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $result = $service->blockUser($account, 'user123');
        

        /* Assert */
        $this->assertFalse($result);
        
    }

    #[Test]
    public function it_returns_false_when_blocking_user_throws_general_exception(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/me/blocked' => Http::response(['error' => 'Network error'], 503),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $result = $service->blockUser($account, 'user123');
        

        /* Assert */
        $this->assertFalse($result);
        
    }

    #[Test]
    public function it_returns_null_when_getting_user_info_without_access_token(): void
    {
        /* Arrange */
        $account = new Account(['username' => 'test']);
        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $userInfo = $service->getUserInfo($account, 'search_user');
        

        /* Assert */
        $this->assertNull($userInfo);
        
    }

    #[Test]
    public function it_returns_first_user_from_search_results(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/search*' => Http::response([
                'data' => [
                    ['id' => 'user123', 'username' => 'search_user'],
                    ['id' => 'user456', 'username' => 'another_user'],
                ],
            ], 200),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $userInfo = $service->getUserInfo($account, 'search_user');
        

        /* Assert */
        $this->assertIsArray($userInfo);
        $this->assertEquals('user123', $userInfo['id']);
        $this->assertEquals('search_user', $userInfo['username']);
        
    }

    #[Test]
    public function it_returns_null_when_user_search_has_no_results(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/search*' => Http::response(['data' => []], 200),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $userInfo = $service->getUserInfo($account, 'nonexistent_user');
        

        /* Assert */
        $this->assertNull($userInfo);
        
    }

    #[Test]
    public function it_returns_null_when_getting_user_info_throws_http_exception(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/search*' => Http::response(['error' => 'API Error'], 500),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $userInfo = $service->getUserInfo($account, 'search_user');
        

        /* Assert */
        $this->assertNull($userInfo);
        
    }

    #[Test]
    public function it_returns_null_when_getting_user_info_throws_general_exception(): void
    {
        /* Arrange */
        Http::fake([
            'https://graph.instagram.com/search*' => Http::response(['error' => 'Network timeout'], 503),
        ]);

        $account = new Account(['username' => 'test']);
        $account->access_token = 'valid_token';

        $client = new ExternalClient;
        $decorator = new HttpClientExceptionDecorator($client);
        $service = new InstagramApiService($decorator);
        

        /* Act */
        $userInfo = $service->getUserInfo($account, 'search_user');
        

        /* Assert */
        $this->assertNull($userInfo);
        
    }
}
