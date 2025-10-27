<?php

namespace App\Services\Instagram;

use App\Enums\RequestMethod;
use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * InstagramApiService provides methods for interacting with Instagram's Graph API.
 * All methods extend the base client functionality with specific Instagram API operations.
 */
class InstagramApiService extends InstagramBaseClient
{
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
            \Log::warning('Failed to block user', [
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
            \Log::warning('Failed to get user info', [
                'account_id' => $account->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
