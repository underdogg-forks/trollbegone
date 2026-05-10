# OAuth and Admin Panel Workflow Tests

## Overview

This document describes the new PHPUnit tests added to cover the Instagram OAuth connection workflow and "For Grandma" admin panel workflows as specified in the problem statement.

## Problem Statement Requirements

The problem statement requested tests for three main areas:

1. **Connect Instagram (Non-Technical Walkthrough)** - OAuth flow that saves/renews tokens
2. **For Grandma: Using the Admin Panel** - Workflows for viewing accounts, following, posts, comments, and blocking users
3. **Token Retrieval and Renewal** - Verification that tokens are stored and used correctly in API requests

## New Test Files Added

### 1. ViewFollowingPageTest.php (7 tests)

**Purpose**: Tests the admin panel page for viewing Instagram following list.

**Coverage**:
- ✅ Displays following list from Instagram API
- ✅ Handles empty following list gracefully
- ✅ Handles API failures gracefully
- ✅ Uses account access token for API requests
- ✅ Provides "View Posts" action for each following
- ✅ Refreshes following list when refresh action is triggered
- ✅ Shows only following for the specific account

**Problem Statement Mapping**: "View Following: Click 'View Following' to see users you follow on Instagram"

### 2. ViewPostsPageTest.php (8 tests)

**Purpose**: Tests the admin panel page for viewing posts from followed users.

**Coverage**:
- ✅ Displays posts for a specific username
- ✅ Handles user not found gracefully
- ✅ Handles user with no posts
- ✅ Handles API failures gracefully
- ✅ Uses account access token for API requests
- ✅ Refreshes posts when refresh action is triggered
- ✅ Formats timestamps for display
- ✅ Provides "Back to Following" action

**Problem Statement Mapping**: "Browse Posts: Click on any user to see their posts"

### 3. OAuthWorkflowIntegrationTest.php (6 tests)

**Purpose**: Tests the complete OAuth workflow with token storage, renewal, and usage verification.

**Coverage**:
- ✅ Stores token during OAuth and uses it for API requests
- ✅ Renews token and uses new token for subsequent API calls
- ✅ Prevents unauthenticated users from connecting Instagram
- ✅ Associates token with correct user in multi-user environment
- ✅ Marks account as active after successful OAuth
- ✅ Updates username when reconnecting with changed username

**Problem Statement Mapping**: 
- "Socialite handles the OAuth2 redirect flow and TrollBeGone stores the returned access token"
- "Reconnecting the same account updates/replaces the token automatically"
- "Token usage in request options, and that renewed tokens are used on subsequent API calls"

### 4. GrandmaWorkflowTest.php (3 tests)

**Purpose**: End-to-end tests for the complete "For Grandma" user journey.

**Coverage**:
- ✅ Completes full workflow from login to blocking troll
  - Login to TrollBeGone
  - Connect Instagram account via OAuth
  - View connected Instagram accounts
  - View following list
  - Browse posts from a followed user
  - Review comments on a post
  - Block users from comments
  - Verifies account's token is used throughout the workflow
- ✅ Isolates workflows for multiple concurrent users
- ✅ Handles workflow with invalid token gracefully

**Problem Statement Mapping**: Complete "For Grandma: Using the Admin Panel" workflow

## Test Patterns and Conventions

All tests follow the project's established patterns:

### 1. Arrange/Act/Assert Pattern
```php
/** #region Arrange */
// Setup test data and dependencies
/** #endregion */

/** #region Act */
// Execute the action being tested
/** #endregion */

/** #region Assert */
// Verify expected outcomes
/** #endregion */
```

### 2. Early Returns and Guard Clauses
Tests verify that error conditions are handled gracefully with early returns.

### 3. Dependency Injection
All dependencies (InstagramApiService, HttpClient) are injected and can be mocked.

### 4. Fake HTTP Client
Uses `FakeHttpClient` from `Tests\Fakes\` to simulate Instagram API responses without making real HTTP requests.

### 5. RefreshDatabase Trait
All feature tests use `RefreshDatabase` to ensure clean database state between tests.

### 6. Descriptive Test Names
Test method names clearly describe what is being tested:
- `it_displays_following_list_from_instagram_api()`
- `it_stores_token_during_oauth_and_uses_it_for_api_requests()`
- `it_completes_full_grandma_workflow_from_login_to_blocking_troll()`

## Token Verification Strategy

The tests verify token management at multiple levels:

1. **Storage**: Assert that tokens are stored in the `instagram_accounts` table
2. **Renewal**: Assert that reconnecting updates the existing token
3. **Usage**: Inspect HTTP request history to verify the correct token is sent in API requests
4. **Isolation**: Verify that each account uses its own token (multi-user scenarios)

## Running the Tests

```bash
# Run all new OAuth and workflow tests
php artisan test tests/Feature/ViewFollowingPageTest.php
php artisan test tests/Feature/ViewPostsPageTest.php
php artisan test tests/Feature/OAuthWorkflowIntegrationTest.php
php artisan test tests/Feature/GrandmaWorkflowTest.php

# Run all feature tests
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage --min=80
```

## Test Statistics

- **Total New Test Files**: 4
- **Total New Tests**: 24
- **Lines of Code**: ~1,200

### Breakdown by Type:
- Feature Tests: 24 tests
- OAuth Tests: 6 tests
- Admin Panel Tests: 15 tests
- End-to-End Tests: 3 tests

## Coverage Verification

These tests cover all requirements from the problem statement:

### OAuth Connection Workflow ✅
- [x] Token storage during OAuth callback
- [x] Token renewal on reconnect
- [x] Token usage in API requests
- [x] Multi-user token isolation

### Admin Panel Workflows ✅
- [x] View Following list
- [x] Browse Posts from followed users
- [x] Review Comments on posts
- [x] Block Users from comments (tested in existing ListCommentsPageTest)

### Token Retrieval and Renewal ✅
- [x] Tokens stored in `instagram_accounts.access_token`
- [x] Reconnecting updates/replaces tokens
- [x] Tokens used in request options
- [x] Renewed tokens used in subsequent calls

### Concurrent User Workflow ✅
- [x] User A → Account A → Blocks user X with Account A's token
- [x] User B → Account B → Blocks user Y with Account B's token
- [x] Operations are independent and thread-safe

## Integration with Existing Tests

The new tests complement existing test coverage:

- **InstagramOAuthControllerTest.php** (existing) - Basic OAuth flow
- **OAuthWorkflowIntegrationTest.php** (new) - Complete OAuth flow with token usage verification
- **MultiAccountWorkflowTest.php** (existing) - Multi-account isolation
- **GrandmaWorkflowTest.php** (new) - End-to-end user journey
- **ListCommentsPageTest.php** (existing) - Comment moderation and blocking

## Notes

1. All tests use Laravel's testing utilities (Livewire::test, Http::fake)
2. Tests follow PSR-12 coding standards
3. Tests include comprehensive PHPDoc comments
4. Tests verify both happy paths and error handling
5. Tests are isolated and can run in any order
6. No real HTTP requests are made (all faked)
7. No real database changes persist between tests (RefreshDatabase)

## Future Enhancements

Potential areas for additional test coverage:

1. Test OAuth token expiration and refresh flow
2. Test rate limiting on Instagram API calls
3. Test pagination for following/posts lists
4. Test bulk blocking operations with queue processing
5. Performance tests for concurrent user workflows
