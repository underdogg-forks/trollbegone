<?php

namespace Tests\Fakes;

use App\Contracts\InstagramApiServiceContract;
use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * FakeInstagramApiService provides a test double for InstagramApiServiceContract.
 * This allows tests to define predictable responses without HTTP calls.
 */
class FakeInstagramApiService implements InstagramApiServiceContract
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
     * @var array<string, Collection>
     */
    protected array $followingResponses = [];

    /**
     * @var array<string, bool>
     */
    protected array $blockUserResults = [];

    /**
     * @var array<int, string>
     */
    protected array $blockUserCalls = [];

    public function setUserInfoResponse(string $username, ?array $userInfo): void
    {
        $this->userInfoResponses[$username] = $userInfo;
    }

    public function setStoriesResponse(string $accountUsername, Collection $stories): void
    {
        $this->storiesResponses[$accountUsername] = $stories;
    }

    public function setCommentsResponse(string $storyId, Collection $comments): void
    {
        $this->commentsResponses[$storyId] = $comments;
    }

    public function setPostCommentsResponse(string $postId, Collection $comments): void
    {
        $this->commentsResponses[$postId] = $comments;
    }

    public function setFollowingResponse(string $accountUsername, Collection $following): void
    {
        $this->followingResponses[$accountUsername] = $following;
    }

    public function setBlockUserResult(string $userId, bool $result): void
    {
        $this->blockUserResults[$userId] = $result;
    }

    public function getFollowing(Account $account): Collection
    {
        return $this->followingResponses[$account->username] ?? collect([]);
    }

    public function getPostsByUsername(Account $account, string $username): Collection
    {
        return collect([]);
    }

    public function getPostComments(Account $account, string $postId): Collection
    {
        return $this->commentsResponses[$postId] ?? collect([]);
    }

    public function filterCommentsByTag(Collection $comments, string $tag): Collection
    {
        $needle = '#'.ltrim(strtolower($tag), '#');

        return $comments->filter(function (array $comment) use ($needle) {
            return str_contains(strtolower((string) ($comment['text'] ?? '')), $needle);
        })->values();
    }

    public function filterCommentsWithoutTags(Collection $comments): Collection
    {
        return $comments->filter(function (array $comment) {
            return ! preg_match('/#[A-Za-z0-9_]+/', (string) ($comment['text'] ?? ''));
        })->values();
    }

    public function deleteComment(Account $account, string $commentId): bool
    {
        return true;
    }

    public function getUserInfo(Account $account, string $username): ?array
    {
        return $this->userInfoResponses[$username] ?? null;
    }

    public function getStories(Account $account): Collection
    {
        return $this->storiesResponses[$account->username] ?? collect([]);
    }

    public function getStoryComments(Account $account, string $storyId): Collection
    {
        return $this->commentsResponses[$storyId] ?? collect([]);
    }

    public function blockUser(Account $account, string $userId): bool
    {
        $this->blockUserCalls[] = $userId;

        return $this->blockUserResults[$userId] ?? true;
    }

    public function getBlockUserCalls(): array
    {
        return $this->blockUserCalls;
    }

    public function reset(): void
    {
        $this->userInfoResponses = [];
        $this->storiesResponses = [];
        $this->commentsResponses = [];
        $this->followingResponses = [];
        $this->blockUserResults = [];
        $this->blockUserCalls = [];
    }
}
