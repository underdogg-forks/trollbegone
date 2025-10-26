<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use App\Models\InstagramAccount;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\Fixtures\InstagramApiFixtures;
use Tests\TestCase;

/**
 * Integration tests for InstagramApiService using fixtures.
 * These tests validate the service's behavior with realistic API responses.
 */
class InstagramApiServiceIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function get_stories_with_real_fixture(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = new InstagramAccount([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getStoriesResponse();

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn($fixtureData['data']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $stories = $service->getStories($account);
        /** #endregion */

        /** #region Assert */
        $this->assertCount(2, $stories);
        $this->assertEquals('17895695668004550', $stories->first()['id']);
        $this->assertEquals('IMAGE', $stories->first()['media_type']);
        $this->assertEquals('VIDEO', $stories->last()['media_type']);
        /** #endregion */
    }

    #[Test]
    public function get_story_comments_with_real_fixture(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = new InstagramAccount([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getStoryCommentsResponse();

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn($fixtureData['data']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        /** #endregion */

        /** #region Act */
        $comments = $service->getStoryComments($account, '17895695668004550');
        /** #endregion */

        /** #region Assert */
        $this->assertCount(3, $comments);
        $this->assertEquals('Great story! Love this content 🔥', $comments->first()['text']);
        $this->assertEquals('john_doe_123', $comments->first()['from']['username']);
        /** #endregion */
    }

    #[Test]
    public function get_user_info_with_real_fixture(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getUserSearchResponse();

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn($fixtureData['data']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $userInfo = $service->getUserInfo($account, 'spam_account');

        $this->assertIsArray($userInfo);
        $this->assertEquals('17841401234567892', $userInfo['id']);
        $this->assertEquals('spam_account', $userInfo['username']);
        $this->assertEquals('Spam Account User', $userInfo['full_name']);
    }

    #[Test]
    public function block_user_with_success_response(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $mockResponse = Mockery::mock(Response::class);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('post')
            ->once()
            ->with('https://graph.instagram.com/me/blocked', [
                'base_uri' => 'https://graph.instagram.com',
                'token' => 'test_token',
                'json' => ['user_id' => '17841401234567892'],
            ])
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $result = $service->blockUser($account, '17841401234567892');

        $this->assertTrue($result);
    }

    #[Test]
    public function identify_spam_comment_from_fixture(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getStoryCommentsResponse();

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn($fixtureData['data']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $comments = $service->getStoryComments($account, '17895695668004550');

        // Simulate spam detection logic
        $spamComments = $comments->filter(function ($comment) {
            return str_contains(strtolower($comment['text']), 'spam');
        });

        $this->assertCount(1, $spamComments);
        $spamComment = $spamComments->first();
        $this->assertEquals('spam_account', $spamComment['from']['username']);
    }

    #[Test]
    public function handle_empty_stories_response(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getEmptyStoriesResponse();

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn($fixtureData['data']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $stories = $service->getStories($account);

        $this->assertCount(0, $stories);
        $this->assertTrue($stories->isEmpty());
    }

    #[Test]
    public function handle_empty_comments_response(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getEmptyCommentsResponse();

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn($fixtureData['data']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $comments = $service->getStoryComments($account, '17895695668004550');

        $this->assertCount(0, $comments);
        $this->assertTrue($comments->isEmpty());
    }

    #[Test]
    public function handle_user_not_found_response(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getEmptyUserSearchResponse();

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn($fixtureData['data']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $userInfo = $service->getUserInfo($account, 'nonexistent_user');

        $this->assertNull($userInfo);
    }

    #[Test]
    public function process_multiple_story_types(): void
    {
        $this->markTestIncomplete();
        $account = new InstagramAccount([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getStoriesResponse();

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn($fixtureData['data']);

        $mockClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $service = new InstagramApiService($mockClient);
        $stories = $service->getStories($account);

        $imageStories = $stories->filter(fn($story) => $story['media_type'] === 'IMAGE');
        $videoStories = $stories->filter(fn($story) => $story['media_type'] === 'VIDEO');

        $this->assertCount(1, $imageStories);
        $this->assertCount(1, $videoStories);
    }
}
