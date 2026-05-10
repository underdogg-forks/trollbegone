<?php

namespace Tests\Fixtures;

/**
 * InstagramApiFixtures provides realistic mock responses from Instagram's Graph API.
 * These fixtures are used in tests to simulate API responses without making actual API calls.
 */
class InstagramApiFixtures
{
    /**
     * Get sample stories response from Instagram Graph API.
     */
    public static function getStoriesResponse(): array
    {
        return [
            'data' => [
                [
                    'id' => '17895695668004550',
                    'media_type' => 'IMAGE',
                    'media_url' => 'https://scontent.cdninstagram.com/story1.jpg',
                    'permalink' => 'https://www.instagram.com/p/ABC123/',
                    'timestamp' => '2024-01-15T14:30:00+0000',
                    'caption' => 'Check out this amazing view!',
                ],
                [
                    'id' => '17895695668004551',
                    'media_type' => 'VIDEO',
                    'media_url' => 'https://scontent.cdninstagram.com/story2.mp4',
                    'permalink' => 'https://www.instagram.com/p/ABC124/',
                    'timestamp' => '2024-01-15T15:45:00+0000',
                    'caption' => 'Amazing sunset today',
                ],
            ],
            'paging' => [
                'cursors' => [
                    'before' => 'QVFIUjBtNm5R...',
                    'after' => 'QVFIUjBtNm5S...',
                ],
            ],
        ];
    }

    /**
     * Get empty stories response (no stories available).
     */
    public static function getEmptyStoriesResponse(): array
    {
        return [
            'data' => [],
        ];
    }

    /**
     * Get sample story comments response from Instagram Graph API.
     */
    public static function getStoryCommentsResponse(): array
    {
        return [
            'data' => [
                [
                    'id' => '17895695668004600',
                    'text' => 'Great story! Love this content 🔥',
                    'from' => [
                        'id' => '17841401234567890',
                        'username' => 'john_doe_123',
                    ],
                    'timestamp' => '2024-01-15T14:35:00+0000',
                ],
                [
                    'id' => '17895695668004601',
                    'text' => 'Amazing! Keep it up! 👏',
                    'from' => [
                        'id' => '17841401234567891',
                        'username' => 'jane_smith_456',
                    ],
                    'timestamp' => '2024-01-15T14:40:00+0000',
                ],
                [
                    'id' => '17895695668004602',
                    'text' => 'This is spam content',
                    'from' => [
                        'id' => '17841401234567892',
                        'username' => 'spam_account',
                    ],
                    'timestamp' => '2024-01-15T14:45:00+0000',
                ],
            ],
        ];
    }

    /**
     * Get empty comments response (no comments available).
     */
    public static function getEmptyCommentsResponse(): array
    {
        return [
            'data' => [],
        ];
    }

    /**
     * Get sample user search response from Instagram Graph API.
     */
    public static function getUserSearchResponse(): array
    {
        return [
            'data' => [
                [
                    'id' => '17841401234567892',
                    'username' => 'spam_account',
                    'full_name' => 'Spam Account User',
                    'profile_picture' => 'https://scontent.cdninstagram.com/profile.jpg',
                ],
                [
                    'id' => '17841401234567893',
                    'username' => 'spam_account_2',
                    'full_name' => 'Another Spam Account',
                    'profile_picture' => 'https://scontent.cdninstagram.com/profile2.jpg',
                ],
            ],
        ];
    }

    /**
     * Get user search response for followed user lookups.
     */
    public static function getFollowedUserSearchResponse(): array
    {
        return [
            'data' => [
                [
                    'id' => 'followed-1',
                    'username' => 'followed_user',
                ],
            ],
        ];
    }

    /**
     * Get media response for a followed user.
     */
    public static function getFollowedUserMediaResponse(): array
    {
        return [
            'data' => [
                ['id' => 'post-1', 'caption' => 'hello'],
                ['id' => 'post-2', 'caption' => 'world'],
            ],
        ];
    }

    /**
     * Get post comments with mixed-case TrollBeGone tags.
     */
    public static function getPostCommentsWithTrollBeGoneTagsResponse(): array
    {
        return [
            'data' => [
                ['id' => 'c1', 'username' => 'alpha', 'text' => 'nice post'],
                ['id' => 'c2', 'username' => 'beta', 'text' => 'spam #TrollBeGone'],
                ['id' => 'c3', 'username' => 'gamma', 'text' => 'rude #trollbegone'],
                ['id' => 'c4', 'username' => 'delta', 'text' => 'also rude #TROLLBEGONE'],
                ['id' => 'c5', 'username' => 'epsilon', 'text' => 'bad #Trollbegone'],
                ['id' => 'c6', 'username' => 'zeta', 'text' => 'mixed #tRoLlBeGoNe'],
            ],
        ];
    }

    /**
     * Get comments fixture for commenter moderation pages.
     *
     * @return array<int, array{id: string, username: string, text: string}>
     */
    public static function getCommenterModerationComments(): array
    {
        return [
            ['id' => 'c1', 'username' => 'alpha', 'text' => 'first'],
            ['id' => 'c2', 'username' => 'alpha', 'text' => 'second'],
            ['id' => 'c3', 'username' => 'beta', 'text' => 'third'],
        ];
    }

    /**
     * Get empty user search response (user not found).
     */
    public static function getEmptyUserSearchResponse(): array
    {
        return [
            'data' => [],
        ];
    }

    /**
     * Get successful block user response from Instagram Graph API.
     */
    public static function getBlockUserSuccessResponse(): array
    {
        return [
            'success' => true,
        ];
    }

    /**
     * Get error response for various API errors.
     *
     * @param  string  $errorType  Type of error (not_found, unauthorized, rate_limit, etc.)
     */
    public static function getErrorResponse(string $errorType = 'generic'): array
    {
        $errors = [
            'not_found' => [
                'error' => [
                    'message' => 'Resource not found',
                    'type' => 'OAuthException',
                    'code' => 404,
                    'fbtrace_id' => 'ABC123DEF456',
                ],
            ],
            'unauthorized' => [
                'error' => [
                    'message' => 'Invalid OAuth access token',
                    'type' => 'OAuthException',
                    'code' => 190,
                    'fbtrace_id' => 'ABC123DEF457',
                ],
            ],
            'rate_limit' => [
                'error' => [
                    'message' => 'Application request limit reached',
                    'type' => 'OAuthException',
                    'code' => 4,
                    'fbtrace_id' => 'ABC123DEF458',
                ],
            ],
            'permission_denied' => [
                'error' => [
                    'message' => 'Permissions error',
                    'type' => 'OAuthException',
                    'code' => 10,
                    'fbtrace_id' => 'ABC123DEF459',
                ],
            ],
            'generic' => [
                'error' => [
                    'message' => 'An unknown error has occurred',
                    'type' => 'OAuthException',
                    'code' => 1,
                    'fbtrace_id' => 'ABC123DEF460',
                ],
            ],
        ];

        return $errors[$errorType] ?? $errors['generic'];
    }

    /**
     * Get a single story data.
     */
    public static function getSingleStory(): array
    {
        return [
            'id' => '17895695668004550',
            'media_type' => 'IMAGE',
            'media_url' => 'https://scontent.cdninstagram.com/story1.jpg',
            'permalink' => 'https://www.instagram.com/p/ABC123/',
            'timestamp' => '2024-01-15T14:30:00+0000',
            'caption' => 'Check out this amazing view!',
        ];
    }

    /**
     * Get a single comment data.
     */
    public static function getSingleComment(): array
    {
        return [
            'id' => '17895695668004600',
            'text' => 'Great story! Love this content 🔥',
            'from' => [
                'id' => '17841401234567890',
                'username' => 'john_doe_123',
            ],
            'timestamp' => '2024-01-15T14:35:00+0000',
        ];
    }

    /**
     * Get a single user data.
     */
    public static function getSingleUser(): array
    {
        return [
            'id' => '17841401234567892',
            'username' => 'test_user',
            'full_name' => 'Test User',
            'profile_picture' => 'https://scontent.cdninstagram.com/profile.jpg',
        ];
    }
}
