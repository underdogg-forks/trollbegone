<?php

namespace Tests\Fakes;

use App\Models\Account;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Support\Collection;

/**
 * FakeInstagramApiService provides a fake implementation of InstagramApiService for testing.
 * This allows tests to define predictable responses without needing Mockery.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class FakeInstagramApiService extends InstagramApiService
{
    /**
     * @var array<string, array>
     */
    protected array $userInfoResponses = [];

    /**
     * @var array<string, Collection>
     */
    protected array $storiesResponses = [];

    /**
     * @var array<string, Collection>
     */
    protected array $commentsResponses = [];

    /**
     * @var array<string, bool>
     */
    protected array $blockUserResults = [];

    /**
     * Override constructor to avoid requiring HttpClientExceptionDecorator.
     */
    public function __construct()
    {
        // Don't call parent constructor - we're a fake
    }

    /**
     * Set the response for getUserInfo.
     *
     * @param  string  $username  Username to match
     * @param  array|null  $userInfo  User info to return
     */
    public function setUserInfoResponse(string $username, ?array $userInfo): void
    {
        $this->userInfoResponses[$username] = $userInfo;
    }

    /**
     * Set the response for getStories.
     *
     * @param  string  $accountUsername  Account username to match
     * @param  Collection  $stories  Stories to return
     */
    public function setStoriesResponse(string $accountUsername, Collection $stories): void
    {
        $this->storiesResponses[$accountUsername] = $stories;
    }

    /**
     * Set the response for getStoryComments.
     *
     * @param  string  $storyId  Story ID to match
     * @param  Collection  $comments  Comments to return
     */
    public function setCommentsResponse(string $storyId, Collection $comments): void
    {
        $this->commentsResponses[$storyId] = $comments;
    }

    /**
     * Set the result for blockUser.
     *
     * @param  string  $userId  User ID to match
     * @param  bool  $result  Success or failure
     */
    public function setBlockUserResult(string $userId, bool $result): void
    {
        $this->blockUserResults[$userId] = $result;
    }

    /**
     * Get user info by username.
     */
    public function getUserInfo(Account $account, string $username): ?array
    {
        return $this->userInfoResponses[$username] ?? null;
    }

    /**
     * Get stories for an account.
     */
    public function getStories(Account $account): Collection
    {
        return $this->storiesResponses[$account->username] ?? collect([]);
    }

    /**
     * Get comments for a story.
     */
    public function getStoryComments(Account $account, string $storyId): Collection
    {
        return $this->commentsResponses[$storyId] ?? collect([]);
    }

    /**
     * Block a user.
     */
    public function blockUser(Account $account, string $userId): bool
    {
        return $this->blockUserResults[$userId] ?? true;
    }

    /**
     * Reset all fake responses.
     */
    public function reset(): void
    {
        $this->userInfoResponses = [];
        $this->storiesResponses = [];
        $this->commentsResponses = [];
        $this->blockUserResults = [];
    }
}
