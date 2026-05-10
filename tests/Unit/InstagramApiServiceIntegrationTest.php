<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\InstagramApiFixtures;
use Tests\TestCase;

class InstagramApiServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gets_stories_with_real_fixture(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getStoriesResponse();

        Http::fake([
            'https://graph.instagram.com/me/stories*' => Http::response($fixtureData, 200),
        ]);

        $service = app(InstagramApiService::class);

        /* Act */
        $stories = $service->getStories($account);

        /* Assert */
        $this->assertCount(2, $stories);
        $this->assertEquals('17895695668004550', $stories->first()['id']);
        $this->assertEquals('IMAGE', $stories->first()['media_type']);
        $this->assertEquals('VIDEO', $stories->last()['media_type']);
    }

    #[Test]
    public function it_gets_story_comments_with_real_fixture(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getStoryCommentsResponse();

        Http::fake([
            'https://graph.instagram.com/17895695668004550/comments*' => Http::response($fixtureData, 200),
        ]);

        $service = app(InstagramApiService::class);

        /* Act */
        $comments = $service->getStoryComments($account, '17895695668004550');

        /* Assert */
        $this->assertCount(3, $comments);
        $this->assertEquals('Great story! Love this content 🔥', $comments->first()['text']);
        $this->assertEquals('john_doe_123', $comments->first()['from']['username']);
    }

    #[Test]
    public function it_gets_user_info_with_real_fixture(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getUserSearchResponse();

        Http::fake([
            'https://graph.instagram.com/search*' => Http::response($fixtureData, 200),
        ]);

        $service = app(InstagramApiService::class);

        /* Act */
        $userInfo = $service->getUserInfo($account, 'spam_account');

        /* Assert */
        $this->assertIsArray($userInfo);
        $this->assertEquals('17841401234567892', $userInfo['id']);
        $this->assertEquals('spam_account', $userInfo['username']);
        $this->assertEquals('Spam Account User', $userInfo['full_name']);
    }

    #[Test]
    public function it_blocks_user_with_success_response(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        Http::fake([
            'https://graph.instagram.com/me/blocked*' => Http::response(['success' => true], 200),
        ]);

        $service = app(InstagramApiService::class);

        /* Act */
        $result = $service->blockUser($account, '17841401234567892');

        /* Assert */
        $this->assertTrue($result);
    }

    #[Test]
    public function it_identifies_spam_comment_from_fixture(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getStoryCommentsResponse();

        Http::fake([
            'https://graph.instagram.com/17895695668004550/comments*' => Http::response($fixtureData, 200),
        ]);

        $service = app(InstagramApiService::class);

        /* Act */
        $comments = $service->getStoryComments($account, '17895695668004550');

        $spamComments = $comments->filter(function ($comment) {
            return str_contains(strtolower($comment['text']), 'spam');
        });

        /* Assert */
        $this->assertCount(1, $spamComments);
        $spamComment = $spamComments->first();
        $this->assertEquals('spam_account', $spamComment['from']['username']);
    }

    #[Test]
    public function it_handles_empty_stories_response(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getEmptyStoriesResponse();

        Http::fake([
            'https://graph.instagram.com/me/stories*' => Http::response($fixtureData, 200),
        ]);

        $service = app(InstagramApiService::class);

        /* Act */
        $stories = $service->getStories($account);

        /* Assert */
        $this->assertCount(0, $stories);
        $this->assertTrue($stories->isEmpty());
    }

    #[Test]
    public function it_handles_empty_comments_response(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getEmptyCommentsResponse();

        Http::fake([
            'https://graph.instagram.com/17895695668004550/comments*' => Http::response($fixtureData, 200),
        ]);

        $service = app(InstagramApiService::class);

        /* Act */
        $comments = $service->getStoryComments($account, '17895695668004550');

        /* Assert */
        $this->assertCount(0, $comments);
        $this->assertTrue($comments->isEmpty());
    }

    #[Test]
    public function it_handles_user_not_found_response(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getEmptyUserSearchResponse();

        Http::fake([
            'https://graph.instagram.com/search*' => Http::response($fixtureData, 200),
        ]);

        $service = app(InstagramApiService::class);

        /* Act */
        $userInfo = $service->getUserInfo($account, 'nonexistent_user');

        /* Assert */
        $this->assertNull($userInfo);
    }

    #[Test]
    public function it_processes_multiple_story_types(): void
    {
        /* Arrange */
        $account = Account::factory()->create([
            'username' => 'test_account',
            'access_token' => 'test_token',
        ]);

        $fixtureData = InstagramApiFixtures::getStoriesResponse();

        Http::fake([
            'https://graph.instagram.com/me/stories*' => Http::response($fixtureData, 200),
        ]);

        $service = app(InstagramApiService::class);

        /* Act */
        $stories = $service->getStories($account);

        $imageStories = $stories->filter(fn ($story) => $story['media_type'] === 'IMAGE');
        $videoStories = $stories->filter(fn ($story) => $story['media_type'] === 'VIDEO');

        /* Assert */
        $this->assertCount(1, $imageStories);
        $this->assertCount(1, $videoStories);
    }
}
