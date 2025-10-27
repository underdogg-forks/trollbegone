# TrollBeGone Development Guidelines

## Core Architecture Principles

TrollBeGone is built on a **multi-account, multi-tenant architecture** where each Instagram account operates independently with its own authentication credentials. This document outlines the fundamental architectural patterns and principles that govern the codebase.

## The Three Pillars

### 1. BaseClient Pattern (ALWAYS)

Every external API integration **MUST** use a BaseClient that encapsulates:

- **API Base URL**: Centralized endpoint configuration
- **API Version**: Version management for the external service
- **Endpoint Management**: String-based endpoint handling
- **Request Wrapper**: Unified request function for all HTTP methods
- **Authentication**: Token/credential handling per request
- **Error Handling**: Consistent exception wrapping

**Example: InstagramBaseClient**
```php
abstract class InstagramBaseClient
{
    protected const BASE_URI = 'https://graph.instagram.com';
    
    public function __construct(
        protected HttpClientExceptionDecorator $httpClient
    ) {}
    
    protected function get(InstagramAccount $account, string $endpoint, array $queryParams = []): Response
    {
        $this->ensureAccessToken($account);
        
        return $this->httpClient->request(
            RequestMethod::GET,
            self::BASE_URI . $endpoint,
            [
                'token' => $account->access_token,
                'query' => $queryParams,
            ]
        );
    }
}
```

### 2. Specific Clients for Specific Endpoints (ALWAYS)

Never create monolithic API clients. **ALWAYS** create focused service classes for specific endpoint groups:

- **InstagramApiService**: Handles Instagram Graph API endpoints
  - `getStories()` → `GET /me/stories`
  - `getStoryComments()` → `GET /{story_id}/comments`
  - `blockUser()` → `POST /me/blocked`
  - `getUserInfo()` → `GET /search`

Each method corresponds to ONE specific endpoint and has ONE responsibility.

**Anti-Pattern ❌**
```php
class ApiClient {
    public function doEverything($endpoint, $method, $data) {
        // BAD: Generic, unclear, hard to test
    }
}
```

**Correct Pattern ✅**
```php
class InstagramApiService extends InstagramBaseClient {
    public function getStories(InstagramAccount $account): Collection {
        $response = $this->get($account, '/me/stories');
        return collect($response->json('data', []));
    }
}
```

### 3. Multi-Account Architecture (ALWAYS)

**CRITICAL**: This application supports **multiple concurrent Instagram accounts** with **multiple concurrent users**.

#### Key Architectural Decisions

1. **No Global API Keys**: NEVER use config-based API keys or tokens
2. **Per-Account Tokens**: Each `InstagramAccount` model has its own `access_token`
3. **Account-Scoped Operations**: ALL API calls require an `InstagramAccount` instance
4. **Concurrent Safety**: 10+ users can operate simultaneously without conflicts

#### The InstagramAccount Model

```php
class InstagramAccount extends Model
{
    protected $fillable = [
        'username',
        'instagram_id',
        'is_active',
        'last_synced_at',
    ];
    
    protected $guarded = [
        'access_token',  // Guarded for security, set separately
    ];
}
```

Each account maintains:
- Its own Instagram Graph API access token
- Its own list of blocked accounts
- Independent sync state
- Active/inactive status

#### User Workflow

```
User A logs in → Browses their account → Sees a post
                 ↓
              Sees a comment they don't like
                 ↓
              Blocks that user using Account A's token

User B logs in → Browses their account → Sees a different post
(simultaneously) ↓
              Blocks a different user using Account B's token
```

Both operations happen **independently** and **concurrently** without any shared state or conflicts.

## HTTP Client Layer

### Three-Layer HTTP Architecture

```
[Service Layer]
      ↓
[InstagramBaseClient] - Uses account-specific tokens
      ↓
[HttpClientExceptionDecorator] - Wraps exceptions consistently
      ↓
[ExternalClient] - Laravel HTTP client wrapper
      ↓
[Laravel HTTP/Guzzle]
```

### ExternalClient

Single-purpose HTTP client wrapper:

```php
class ExternalClient
{
    public function request(string $method, string $url, array $options = []): Response
    {
        return Http::send($method, $url, $options);
    }
}
```

### HttpClientExceptionDecorator

Wraps the ExternalClient to provide consistent exception handling:

```php
class HttpClientExceptionDecorator
{
    public function __construct(
        protected ExternalClient $client
    ) {}
    
    public function request(string $method, string $url, array $options = []): Response
    {
        try {
            $response = $this->client->request($method, $url, $options);
            $response->throw();
            return $response;
        } catch (RequestException $e) {
            throw new HttpClientException(
                message: $e->getMessage(),
                code: $e->response?->status() ?? 0,
                previous: $e
            );
        }
    }
}
```

## Service Layer Pattern

### BlockedAccountService

Business logic layer that orchestrates API calls and database operations:

```php
class BlockedAccountService
{
    public function __construct(
        protected InstagramApiService $instagramApi
    ) {}
    
    public function blockAccount(
        InstagramAccount $account,
        string $username,
        ?string $reason = null,
        ?string $commentText = null
    ): BlockedAccount {
        // Guard clause
        if (!$account->id) {
            throw new \Exception('Account must be saved before blocking users');
        }
        
        return DB::transaction(function () use ($account, $username, $reason, $commentText) {
            // Get user info (non-critical if it fails)
            $userInfo = null;
            try {
                $userInfo = $this->instagramApi->getUserInfo($account, $username);
            } catch (\Exception $e) {
                // Continue without user info
            }
            
            // Create local record
            $blockedAccount = BlockedAccount::create([
                'instagram_account_id' => $account->id,
                'blocked_username' => $username,
                'blocked_instagram_id' => $userInfo['id'] ?? null,
                'reason' => $reason,
                'comment_text' => $commentText,
            ]);
            
            // Try to block on Instagram (non-critical if it fails)
            if ($userInfo && isset($userInfo['id'])) {
                $this->instagramApi->blockUser($account, $userInfo['id']);
            }
            
            return $blockedAccount;
        });
    }
}
```

## SOLID Principles in Practice

### Single Responsibility Principle

Each class has ONE job:

- `ExternalClient`: Make HTTP requests
- `HttpClientExceptionDecorator`: Handle HTTP exceptions
- `InstagramBaseClient`: Provide authenticated Instagram API request methods
- `InstagramApiService`: Call specific Instagram endpoints
- `BlockedAccountService`: Manage blocking business logic
- `InstagramAccount`: Represent an Instagram account
- `BlockedAccount`: Represent a blocked user

### Open/Closed Principle

Extend behavior through:

- **Inheritance**: `InstagramApiService extends InstagramBaseClient`
- **Decoration**: `HttpClientExceptionDecorator` wraps `ExternalClient`
- **Composition**: Services inject dependencies

### Liskov Substitution Principle

All subclasses are substitutable:

- Any `InstagramBaseClient` implementation can be swapped
- Mock implementations in tests are drop-in replacements

### Interface Segregation Principle

Small, focused interfaces:

- Each service has minimal, focused public methods
- No fat interfaces with unused methods

### Dependency Inversion Principle

Depend on abstractions, inject dependencies:

```php
// ✅ GOOD
public function __construct(
    protected InstagramApiService $instagramApi,
    protected HttpClientExceptionDecorator $httpClient
) {}

// ❌ BAD
public function blockUser() {
    $api = new InstagramApiService(); // Never do this!
}
```

## Early Returns & Guard Clauses

**CRITICAL**: This is our most important coding pattern.

### Always Check Preconditions First

```php
public function blockUser(InstagramAccount $account, string $userId): bool
{
    // Guard clause - fail fast
    if (!$account->access_token) {
        throw new Exception("No access token for account: {$account->username}");
    }
    
    try {
        $this->post($account, '/me/blocked', ['user_id' => $userId]);
        return true;
    } catch (\Exception $e) {
        // Early return on error
        return false;
    }
}
```

### Flatten Your Code

**Anti-Pattern ❌**
```php
public function process($data) {
    if ($data) {
        if ($data->isValid()) {
            if ($data->hasPermission()) {
                // Deep nesting is hard to read
                return $this->doWork($data);
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}
```

**Correct Pattern ✅**
```php
public function process($data) {
    // Guard clauses - early returns
    if (!$data) {
        return false;
    }
    
    if (!$data->isValid()) {
        return false;
    }
    
    if (!$data->hasPermission()) {
        return false;
    }
    
    // Happy path - not nested
    return $this->doWork($data);
}
```

## Exception Handling Strategy

### Critical vs Non-Critical Operations

**Critical Operations** - Throw exceptions:
- Configuration errors
- Missing dependencies
- Data integrity violations
- Authentication failures

```php
protected function ensureAccessToken(InstagramAccount $account): void
{
    if (!$account->access_token) {
        throw new Exception("No access token for account: {$account->username}");
    }
}
```

**Non-Critical Operations** - Return null/false:
- External API calls (may timeout)
- Optional features
- Search operations
- User lookups

```php
public function getUserInfo(InstagramAccount $account, string $username): ?array
{
    try {
        $response = $this->get($account, '/search', ['q' => $username, 'type' => 'user']);
        $users = $response->json('data', []);
        return $users[0] ?? null;
    } catch (\Exception $e) {
        // Silently fail - user search is non-critical
        return null;
    }
}
```

## Modern PHP Features

### Constructor Property Promotion (PHP 8.0+)

**ALWAYS** use constructor property promotion:

```php
// ✅ GOOD
public function __construct(
    protected InstagramApiService $instagramApi,
    protected HttpClientExceptionDecorator $httpClient
) {}

// ❌ BAD
protected $instagramApi;
public function __construct(InstagramApiService $instagramApi) {
    $this->instagramApi = $instagramApi;
}
```

### Named Parameters (PHP 8.0+)

Use named parameters for clarity:

```php
BlockedAccount::create([
    'instagram_account_id' => $account->id,
    'blocked_username' => $username,
    'blocked_instagram_id' => $userInfo['id'] ?? null,
    'reason' => $reason,
    'comment_text' => $commentText,
]);

throw new HttpClientException(
    message: $e->getMessage(),
    code: $e->response?->status() ?? 0,
    previous: $e
);
```

### Type Hints (ALWAYS)

**ALWAYS** type hint parameters and return types:

```php
// ✅ GOOD
public function blockUser(InstagramAccount $account, string $userId): bool
public function getUserInfo(InstagramAccount $account, string $username): ?array
public function getStories(InstagramAccount $account): Collection

// ❌ BAD - No type hints
public function blockUser($account, $userId)
```

## Testing Strategy

### Test Structure Pattern

Use `#region` pattern for Arrange/Act/Assert:

```php
#[Test]
public function blocking_account_creates_database_record(): void
{
    /** #region Arrange */
    $account = InstagramAccount::create([
        'username' => 'main_account',
        'access_token' => 'test_token',
    ]);
    $mockHttpClient = Mockery::mock(HttpClientExceptionDecorator::class);
    // ... setup mocks
    /** #endregion */

    /** #region Act */
    $service->blockAccount($account, 'spammer', 'Spam comments');
    /** #endregion */

    /** #region Assert */
    $this->assertDatabaseHas('blocked_accounts', [
        'instagram_account_id' => $account->id,
        'blocked_username' => 'spammer',
    ]);
    /** #endregion */
}
```

### Test Coverage Requirements

- **Unit Tests**: All service classes, HTTP clients
- **Feature Tests**: End-to-end workflows, database interactions
- **Integration Tests**: External API interactions (use fixtures)
- **Minimum**: 13-15 tests per service class

## Security Best Practices

### Never Commit Secrets

- Use `.env` for all credentials
- Add placeholders to `.env.example`
- Never commit actual tokens or keys

### Access Token Security

```php
class InstagramAccount extends Model
{
    protected $fillable = [
        'username',
        'instagram_id',
        'is_active',
        'last_synced_at',
    ];
    
    protected $guarded = [
        'access_token',  // Guarded - must be set separately
    ];
}
```

### User-Facing Error Messages

**NEVER** expose internal errors to users:

```php
try {
    $service->blockAccount($account, $username);
    
    Notification::make()
        ->title('User blocked successfully')
        ->success()
        ->send();
} catch (\Exception $e) {
    // Log detailed error for debugging
    Log::error('Failed to block user', [
        'account' => $account->username,
        'target' => $username,
        'error' => $e->getMessage(),
    ]);
    
    // Show user-friendly message
    Notification::make()
        ->title('Failed to block user')
        ->body('Please try again later')
        ->danger()
        ->send();
}
```

## Database Relationships

### InstagramAccount → BlockedAccount (One-to-Many)

```php
class InstagramAccount extends Model
{
    public function blockedAccounts(): HasMany
    {
        return $this->hasMany(BlockedAccount::class);
    }
}

class BlockedAccount extends Model
{
    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }
}
```

### Cascade Behavior

**IMPORTANT**: Deleting an `InstagramAccount` does NOT cascade delete `BlockedAccount` records. This preserves historical blocking data for audit purposes.

## Filament Integration

### Resource Organization

```
app/Filament/Resources/
├── InstagramAccounts/
│   ├── InstagramAccountResource.php
│   ├── Pages/
│   │   ├── ListInstagramAccounts.php
│   │   ├── CreateInstagramAccount.php
│   │   └── EditInstagramAccount.php
│   └── Tables/
│       └── InstagramAccountTable.php
└── BlockedAccounts/
    ├── BlockedAccountResource.php
    ├── Pages/
    │   └── ListBlockedAccounts.php
    └── RelationManagers/
        └── BlockedAccountsRelationManager.php
```

### Custom Actions

Actions in Filament resources should:
- Delegate to services (never contain business logic)
- Provide clear success/error notifications
- Use modals for confirmations
- Handle exceptions gracefully

## Code Quality Checklist

Before committing, verify:

- [ ] All methods have guard clauses and early returns
- [ ] No nesting deeper than 2 levels
- [ ] All dependencies injected via constructor
- [ ] All parameters and return types are type-hinted
- [ ] Comprehensive PHPDoc on all public methods
- [ ] Each class has a single, clear responsibility
- [ ] Exceptions thrown for critical errors, null/false for non-critical
- [ ] Tests written with #region Arrange/Act/Assert pattern
- [ ] Code formatted with `./vendor/bin/pint`
- [ ] All tests pass with `php artisan test`

## Common Anti-Patterns to Avoid

### ❌ Global API Keys

```php
// BAD - Don't do this!
class InstagramService {
    public function getStories() {
        $token = config('services.instagram.api_key'); // NO!
        // This doesn't work with multiple accounts
    }
}
```

### ❌ Direct Instantiation

```php
// BAD - Don't do this!
public function blockUser() {
    $api = new InstagramApiService(); // NO!
    // Use dependency injection
}
```

### ❌ Deep Nesting

```php
// BAD - Don't do this!
public function process($data) {
    if ($data) {
        if ($data->isValid()) {
            if ($data->hasPermission()) {
                // Too deep!
            }
        }
    }
}
```

### ❌ Monolithic Services

```php
// BAD - Don't do this!
class InstagramService {
    public function doEverything() {} // NO!
    // Split into focused services
}
```

## Future Considerations

### OAuth Flow

The application is designed to support OAuth authentication flow:

```php
// config/services.php
'instagram' => [
    'client_id' => env('INSTAGRAM_CLIENT_ID'),
    'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
    'redirect' => env('INSTAGRAM_REDIRECT_URI'),
],
```

Each user will be able to:
1. Click "Connect Instagram Account"
2. Authorize via OAuth
3. Application receives access token
4. Token stored with the InstagramAccount
5. User can now manage their account independently

### Token Refresh

Access tokens expire. Future implementation should:
- Detect expired tokens (401/403 responses)
- Trigger OAuth refresh flow
- Update token in database
- Retry failed request

### Rate Limiting

Instagram has rate limits. Implementation should:
- Track API calls per account
- Implement exponential backoff
- Queue heavy operations
- Handle 429 responses gracefully

## Resources

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Filament Documentation](https://filamentphp.com/docs/4.x/admin)
- [Instagram Graph API](https://developers.facebook.com/docs/instagram-api)
- [PSR-12 Style Guide](https://www.php-fig.org/psr/psr-12/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)

## Summary

TrollBeGone follows three core principles:

1. **BaseClient Pattern**: Every API has a base client with URL, version, endpoint, request wrapper, and authentication
2. **Specific Clients**: One client per endpoint group, one method per endpoint
3. **Multi-Account Architecture**: No global keys, per-account tokens, concurrent-safe operations

These principles ensure the application is:
- **Scalable**: Supports unlimited Instagram accounts
- **Maintainable**: Clear separation of concerns
- **Testable**: Each component can be tested in isolation
- **Secure**: No shared credentials or global state
- **Concurrent**: Multiple users can operate simultaneously

When in doubt, remember: **ALWAYS use BaseClient, ALWAYS use specific endpoint clients, ALWAYS use per-account tokens.**
