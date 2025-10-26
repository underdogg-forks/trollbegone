<?php

namespace Tests;

use Tests\Fixtures\InstagramApiFixtures;

/**
 * Abstract test case for reusable fixtures and test helpers.
 *
 * This class provides common fixtures and helper methods used across tests.
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * Get Instagram API fixtures helper.
     *
     * @return InstagramApiFixtures
     */
    protected function fixtures(): InstagramApiFixtures
    {
        return new InstagramApiFixtures();
    }

    /**
     * Get sample stories response from Instagram Graph API.
     *
     * @return array
     */
    protected function getStoriesResponse(): array
    {
        return InstagramApiFixtures::getStoriesResponse();
    }

    /**
     * Get empty stories response (no stories available).
     *
     * @return array
     */
    protected function getEmptyStoriesResponse(): array
    {
        return InstagramApiFixtures::getEmptyStoriesResponse();
    }

    /**
     * Get sample story comments response from Instagram Graph API.
     *
     * @return array
     */
    protected function getStoryCommentsResponse(): array
    {
        return InstagramApiFixtures::getStoryCommentsResponse();
    }

    /**
     * Get empty comments response (no comments available).
     *
     * @return array
     */
    protected function getEmptyCommentsResponse(): array
    {
        return InstagramApiFixtures::getEmptyCommentsResponse();
    }

    /**
     * Get sample user search response from Instagram Graph API.
     *
     * @return array
     */
    protected function getUserSearchResponse(): array
    {
        return InstagramApiFixtures::getUserSearchResponse();
    }

    /**
     * Get empty user search response (user not found).
     *
     * @return array
     */
    protected function getEmptyUserSearchResponse(): array
    {
        return InstagramApiFixtures::getEmptyUserSearchResponse();
    }

    /**
     * Get successful block user response from Instagram Graph API.
     *
     * @return array
     */
    protected function getBlockUserSuccessResponse(): array
    {
        return InstagramApiFixtures::getBlockUserSuccessResponse();
    }

    /**
     * Get error response for various API errors.
     *
     * @param string $errorType Type of error (not_found, unauthorized, rate_limit, etc.)
     * @return array
     */
    protected function getErrorResponse(string $errorType = 'generic'): array
    {
        return InstagramApiFixtures::getErrorResponse($errorType);
    }

    /**
     * Get a single story data.
     *
     * @return array
     */
    protected function getSingleStory(): array
    {
        return InstagramApiFixtures::getSingleStory();
    }

    /**
     * Get a single comment data.
     *
     * @return array
     */
    protected function getSingleComment(): array
    {
        return InstagramApiFixtures::getSingleComment();
    }

    /**
     * Get a single user data.
     *
     * @return array
     */
    protected function getSingleUser(): array
    {
        return InstagramApiFixtures::getSingleUser();
    }
}
