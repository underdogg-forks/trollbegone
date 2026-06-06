<?php

namespace App\Services\Instagram;

use App\Contracts\InstagramApiServiceContract;
use App\Enums\RequestMethod;
use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * InstagramApiService provides methods for interacting with Instagram's Graph API.
 * All methods extend the base client functionality with specific Instagram API operations.
 */
class InstagramApiService extends InstagramBaseClient implements InstagramApiServiceContract
{
    /**
     * Get users followed by the authenticated account.
     *
     * API Endpoint: GET /me/following
     * Response example: {"data": [{"id": "1784...", "username": "example_user"}]}
     */
    public function getFollowing(Account $account): Collection
    {
        $response = $this->request(RequestMethod::GET, $account, '/me/following');

        return collect($response->json('data', []));
    }

    /**
     * Get posts for a specific Instagram username.
     *
     * API Endpoints:
     * - GET /search?q={username}&type=user
     * - GET /{user_id}/media
     * Response example: {"data": [{"id": "media_1", "caption": "Hello"}]}
     */
    public function getPostsByUsername(Account $account, string $username): Collection
    {
        $userInfo = $this->getUserInfo($account, $username);

        if (! $userInfo || ! isset($userInfo['id'])) {
            return collect([]);
        }

        $response = $this->request(RequestMethod::GET, $account, "/{$userInfo['id']}/media");

        return collect($response->json('data', []));
    }

    /**
     * Get comments for a specific post.
     *
     * API Endpoint: GET /{post_id}/comments
     * Response example: {"data": [{"id": "comment_1", "text": "Nice!", "username": "user"}]}
     */
    public function getPostComments(Account $account, string $postId): Collection
    {
        $response = $this->request(RequestMethod::GET, $account, "/{$postId}/comments");

        return collect($response->json('data', []));
    }

    /**
     * Filter comments that include a moderation hash tag.
     *
     * Example input:
     * [
     *   {"id": "comment_1", "text": "hello #TrollBeGone"},
     *   {"id": "comment_2", "text": "normal comment"}
     * ]
     *
     * Example output:
     * [
     *   {"id": "comment_1", "text": "hello #TrollBeGone"}
     * ]
     */
    public function filterCommentsByTag(Collection $comments, string $tag): Collection
    {
        $needle = '#'.ltrim(strtolower($tag), '#');

        return $comments->filter(function (array $comment) use ($needle) {
            $text = strtolower((string) ($comment['text'] ?? ''));

            return str_contains($text, $needle);
        })->values();
    }

    /**
     * Filter comments that do not contain any hash tags.
     *
     * Example input:
     * [
     *   {"id": "comment_1", "text": "hello #TrollBeGone"},
     *   {"id": "comment_2", "text": "normal comment"}
     * ]
     *
     * Example output:
     * [
     *   {"id": "comment_2", "text": "normal comment"}
     * ]
     */
    public function filterCommentsWithoutTags(Collection $comments): Collection
    {
        return $comments->filter(function (array $comment) {
            $text = (string) ($comment['text'] ?? '');

            return ! preg_match('/#[A-Za-z0-9_]+/', $text);
        })->values();
    }

    /**
     * Delete a specific comment.
     *
     * API Endpoint: DELETE /{comment_id}
     * Response example: {"success": true}
     */
    public function deleteComment(Account $account, string $commentId): bool
    {
        try {
            $this->request(RequestMethod::DELETE, $account, "/{$commentId}");

            return true;
        } catch (\Exception) {
            return false;
        }
    }
    /**
     * Get stories for an Instagram account.
     *
     * API Endpoint: GET /me/stories
     * Response example:
     * {
     *   "data": [
     *     {
     *       "id": "story_id_123",
     *       "media_type": "IMAGE",
     *       "media_url": "https://example.com/story.jpg",
     *       "permalink": "https://instagram.com/p/abc123",
     *       "timestamp": "2024-01-01T00:00:00+0000"
     *     }
     *   ],
     *   "paging": {
     *     "cursors": {
     *       "before": "cursor_before",
     *       "after": "cursor_after"
     *     }
     *   }
     * }
     *
     * @param  Account  $account  The account to fetch stories for
     * @return Collection Collection of story objects
     *
     * @throws \Exception If no access token is available
     */
    public function getStories(Account $account): Collection
    {
        $response = $this->request(RequestMethod::GET, $account, '/me/stories');

        return collect($response->json('data', []));
    }

    /**
     * Get comments for a specific story.
     *
     * API Endpoint: GET /{story_id}/comments
     * Response example:
     * {
     *   "data": [
     *     {
     *       "id": "comment_id_123",
     *       "text": "Great story!",
     *       "from": {
     *         "id": "user_id_456",
     *         "username": "john_doe"
     *       },
     *       "timestamp": "2024-01-01T00:00:00+0000"
     *     }
     *   ]
     * }
     *
     * @param  Account  $account  The account
     * @param  string  $storyId  The ID of the story
     * @return Collection Collection of comment objects
     *
     * @throws \Exception If no access token is available
     */
    public function getStoryComments(Account $account, string $storyId): Collection
    {
        $response = $this->request(RequestMethod::GET, $account, "/{$storyId}/comments");

        return collect($response->json('data', []));
    }

    /**
     * Block a user on Instagram.
     *
     * API Endpoint: POST /me/blocked
     * Request payload:
     * {
     *   "user_id": "instagram_user_id_to_block"
     * }
     *
     * Response example:
     * {
     *   "success": true
     * }
     *
     * @param  Account  $account  The account
     * @param  string  $userId  The Instagram user ID to block
     * @return bool True if successful, false otherwise
     *
     * @throws \Exception If no access token is available
     */
    public function blockUser(Account $account, string $userId): bool
    {
        try {
            $this->request(RequestMethod::POST, $account, '/me/blocked', [
                'json' => ['user_id' => $userId],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to block user', [
                'account_id' => $account->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Search for a user by username.
     *
     * API Endpoint: GET /search
     * Query parameters:
     * - q: search query (username)
     * - type: "user"
     *
     * Response example:
     * {
     *   "data": [
     *     {
     *       "id": "user_id_123",
     *       "username": "searched_username",
     *       "full_name": "Full Name",
     *       "profile_picture": "https://example.com/profile.jpg"
     *     }
     *   ]
     * }
     *
     * @param  Account  $account  The account
     * @param  string  $username  The username to search for
     * @return array|null The first user found, or null if not found or on error
     *
     * @throws \Exception If no access token is available
     */
    public function getUserInfo(Account $account, string $username): ?array
    {
        try {
            $response = $this->request(RequestMethod::GET, $account, '/search', [
                'query' => [
                    'q' => $username,
                    'type' => 'user',
                ],
            ]);

            $users = $response->json('data', []);

            return $users[0] ?? null;
        } catch (\Exception $e) {
            Log::warning('Failed to get user info', [
                'account_id' => $account->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
