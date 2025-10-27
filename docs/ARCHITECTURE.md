# External Client Architecture Refactoring

This document summarizes the comprehensive refactoring of the external client architecture for TrollBeGone.

## Overview

The refactoring aligns the HTTP client architecture with industry standards (GuzzleHttp\Client) and adds comprehensive OAuth authentication for Instagram.

## Key Changes

### 1. ExternalClient Simplification

**Before:**
- Multiple methods: `get()`, `post()`, `put()`, `delete()`, `patch()`
- Inconsistent with GuzzleHttp\Client interface

**After:**
- Single `request()` method that accepts HTTP method, URL, and options
- Mimics GuzzleHttp\Client interface for consistency
- Comprehensive PHPDoc documentation

```php
// Usage
$client = new ExternalClient();
$response = $client->request('GET', 'https://api.example.com/endpoint', [
    'headers' => ['Authorization' => 'Bearer token'],
    'timeout' => 30,
]);
```

### 2. HttpClientExceptionDecorator Enhancement

**Features:**
- Uses `__call()` magic method for backward compatibility
- Supports method calls like `get()`, `post()`, etc. via magic method
- Wraps all exceptions in `HttpClientException`
- Preserves HTTP status codes and error messages

```php
// Both approaches work
$decorator->request('GET', $url);  // Direct method
$decorator->get($url);             // Magic method (backward compatible)
```

### 3. InstagramBaseClient

**New Abstract Class:**
- Provides authenticated request methods for Instagram API
- Centralizes access token validation
- Reduces code duplication across Instagram services

**Protected Methods:**
- `get()` - Authenticated GET requests
- `post()` - Authenticated POST requests
- `put()` - Authenticated PUT requests
- `delete()` - Authenticated DELETE requests
- `ensureAccessToken()` - Validates access token presence

### 4. Comprehensive PHPDoc Documentation

All API methods now include:
- Endpoint documentation
- Request payload examples (JSON)
- Response examples (JSON)
- Parameter descriptions
- Exception documentation

Example:

```php
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
 */
```

### 5. Test Fixtures

**InstagramApiFixtures Class:**
- Realistic API response data
- Multiple response scenarios (success, error, empty)
- Error responses for different error types
- Reusable across all tests

**Fixture Categories:**
- Stories responses
- Comment responses
- User search responses
- Block user responses
- Error responses (401, 404, 500, rate limits, etc.)

### 6. Integration Tests

**15 New Integration Tests:**
- Tests with realistic API fixtures
- Complete workflow validation
- Error handling verification
- Edge case coverage

**Test Coverage:**
- Instagram API service (9 tests)
- Blocked account service (6 tests)
- All scenarios: success, failure, empty data, errors

### 7. Laravel Socialite Integration

**OAuth Flow:**
1. User clicks "Connect Instagram Account" in Filament
2. Redirects to Instagram OAuth authorization
3. User authorizes the app
4. Callback receives access token
5. Account saved with token in database

**Components:**
- `InstagramOAuthController` - Handles OAuth flow
- `InstagramProvider` - Custom Socialite provider for Instagram
- Routes for redirect, callback, and disconnect
- Filament action button for easy connection

**Configuration:**

```env
INSTAGRAM_CLIENT_ID=your_app_id
INSTAGRAM_CLIENT_SECRET=your_app_secret
INSTAGRAM_REDIRECT_URI=https://yourdomain.com/auth/instagram/callback
```

### 8. Enhanced Exception Handling

**Strategy:**
- All external API exceptions are caught
- Wrapped in `HttpClientException` with context
- Methods like `blockUser()` and `getUserInfo()` handle exceptions gracefully
- Return `false` or `null` on failure instead of throwing

**Benefits:**
- Prevents application crashes from API failures
- Provides better error messages
- Maintains application stability

## File Structure

```text
app/
├── Http/
│   └── Controllers/
│       └── InstagramOAuthController.php (NEW)
├── Providers/
│   ├── AppServiceProvider.php (UPDATED)
│   └── InstagramProvider.php (NEW)
└── Services/
    ├── Http/
    │   ├── ExternalClient.php (REFACTORED)
    │   ├── HttpClientException.php
    │   └── HttpClientExceptionDecorator.php (REFACTORED)
    └── Instagram/
        ├── InstagramBaseClient.php (NEW)
        ├── InstagramApiService.php (REFACTORED)
        └── BlockedAccountService.php (UPDATED)

tests/
├── Fixtures/
│   └── InstagramApiFixtures.php (NEW)
└── Unit/
    ├── BlockedAccountServiceIntegrationTest.php (NEW)
    ├── InstagramApiServiceIntegrationTest.php (NEW)
    ├── ExternalClientTest.php (UPDATED)
    └── InstagramApiServiceTest.php (UPDATED)

docs/
└── INSTAGRAM_OAUTH.md (NEW)

config/
└── services.php (UPDATED)

routes/
└── web.php (UPDATED)
```

## Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# External client tests
php artisan test --filter ExternalClient

# Integration tests with fixtures
php artisan test --filter Integration

# Instagram API tests
php artisan test --filter InstagramApi
```

### Test Results
- **78 tests passing**
- 149 assertions
- Comprehensive coverage of all new features

## Migration Guide

### For Developers Using ExternalClient

**Before:**

```php
$client = new ExternalClient();
$response = $client->get($url, $options);
```

**After (Recommended):**

```php
$client = new ExternalClient();
$response = $client->request('GET', $url, $options);
```

**After (Backward Compatible via Decorator):**

```php
$decorator = new HttpClientExceptionDecorator($client);
$response = $decorator->get($url, $options); // Still works!
```

### For Services Extending Base Client

Services now extend `InstagramBaseClient`:

```php
class InstagramApiService extends InstagramBaseClient
{
    public function getStories(InstagramAccount $account): Collection
    {
        $response = $this->get($account, '/me/stories');
        return collect($response->json('data', []));
    }
}
```

## Security Improvements

1. **OAuth Implementation**: Secure token management
2. **Environment Variables**: Credentials stored in .env
3. **Exception Wrapping**: Prevents information leakage
4. **Token Validation**: Ensures valid tokens before API calls
5. **HTTPS Enforcement**: Required for production OAuth

## Documentation

- **INSTAGRAM_OAUTH.md**: Complete OAuth setup guide
- **ARCHITECTURE.md**: This file
- **PHPDoc**: Inline documentation in all classes

## Benefits

1. **Consistency**: Matches GuzzleHttp\Client interface
2. **Maintainability**: Reduced code duplication
3. **Testability**: Comprehensive fixtures and tests
4. **Security**: Proper exception handling and OAuth
5. **Documentation**: Extensive PHPDoc and guides
6. **Flexibility**: Backward compatible via `__call()`
7. **User Experience**: Easy Instagram account connection via Filament

## Next Steps

1. Deploy to staging environment
2. Test OAuth flow with real Instagram accounts
3. Monitor error logs for exception handling
4. Add token refresh logic for long-lived tokens
5. Consider adding more OAuth providers (Facebook, Twitter, etc.)

## Support

For questions or issues:
1. Check the documentation in `docs/`
2. Review test examples in `tests/`
3. Examine PHPDoc in source files
