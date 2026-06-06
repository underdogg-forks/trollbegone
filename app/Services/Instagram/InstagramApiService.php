<?php

namespace App\Services\Instagram;

use App\Contracts\InstagramApiServiceContract;
use App\Models\Account;
use App\Services\Instagram\Endpoints\BlockUserEndpoint;
use App\Services\Instagram\Endpoints\DeleteCommentEndpoint;
use App\Services\Instagram\Endpoints\GetFollowingEndpoint;
use App\Services\Instagram\Endpoints\GetPostCommentsEndpoint;
use App\Services\Instagram\Endpoints\GetPostsByUsernameEndpoint;
use App\Services\Instagram\Endpoints\GetStoriesEndpoint;
use App\Services\Instagram\Endpoints\GetStoryCommentsEndpoint;
use App\Services\Instagram\Endpoints\GetUserInfoEndpoint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class InstagramApiService implements InstagramApiServiceContract
{
    public function __construct(
        private readonly GetFollowingEndpoint $getFollowingEndpoint,
        private readonly GetStoriesEndpoint $getStoriesEndpoint,
        private readonly GetStoryCommentsEndpoint $getStoryCommentsEndpoint,
        private readonly GetPostCommentsEndpoint $getPostCommentsEndpoint,
        private readonly GetPostsByUsernameEndpoint $getPostsByUsernameEndpoint,
        private readonly GetUserInfoEndpoint $getUserInfoEndpoint,
        private readonly BlockUserEndpoint $blockUserEndpoint,
        private readonly DeleteCommentEndpoint $deleteCommentEndpoint,
    ) {}

    public function getFollowing(Account $account): Collection
    {
        return $this->getFollowingEndpoint->execute($account);
    }

    public function getStories(Account $account): Collection
    {
        return $this->getStoriesEndpoint->execute($account);
    }

    public function getStoryComments(Account $account, string $storyId): Collection
    {
        return $this->getStoryCommentsEndpoint->execute($account, $storyId);
    }

    public function getPostComments(Account $account, string $postId): Collection
    {
        return $this->getPostCommentsEndpoint->execute($account, $postId);
    }

    public function getPostsByUsername(Account $account, string $username): Collection
    {
        return $this->getPostsByUsernameEndpoint->execute($account, $username);
    }

    public function getUserInfo(Account $account, string $username): ?array
    {
        try {
            return $this->getUserInfoEndpoint->execute($account, $username);
        } catch (\Exception $e) {
            Log::warning('Failed to get user info', [
                'account_id' => $account->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function blockUser(Account $account, string $userId): bool
    {
        try {
            return $this->blockUserEndpoint->execute($account, $userId);
        } catch (\Exception $e) {
            Log::warning('Failed to block user', [
                'account_id' => $account->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function deleteComment(Account $account, string $commentId): bool
    {
        try {
            return $this->deleteCommentEndpoint->execute($account, $commentId);
        } catch (\Exception) {
            return false;
        }
    }

    public function filterCommentsByTag(Collection $comments, string $tag): Collection
    {
        $needle = '#'.ltrim(strtolower($tag), '#');

        return $comments->filter(function (array $comment) use ($needle) {
            $text = strtolower((string) ($comment['text'] ?? ''));

            return str_contains($text, $needle);
        })->values();
    }

    public function filterCommentsWithoutTags(Collection $comments): Collection
    {
        return $comments->filter(function (array $comment) {
            $text = (string) ($comment['text'] ?? '');

            return ! preg_match('/#[A-Za-z0-9_]+/', $text);
        })->values();
    }
}
