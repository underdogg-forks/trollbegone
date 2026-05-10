# TrollBeGone

A Laravel 12 application with Filament v4 admin panel for managing Instagram accounts, browsing users you follow, viewing posts and comments, and blocking unwanted accounts via the Instagram Graph API.

## 🎯 Overview

TrollBeGone helps "Grandma" easily manage her Instagram account by providing a user-friendly interface to:
- View users she follows on Instagram
- Browse posts from specific users
- View comments on posts
- Select and block multiple users from comments with a single click

Built on a clean **API Client** architecture using a single `request()` method pattern, TrollBeGone integrates seamlessly with the Instagram Graph API while maintaining code simplicity and consistency.

### Key Features

- ✅ **Filament Admin Panel**: Beautiful, intuitive UI for managing Instagram interactions
- ✅ **Multi-Account Support**: Manage unlimited Instagram accounts independently
- ✅ **API-Driven UI**: View following, posts, and comments without database tables
- ✅ **Bulk Blocking**: Select multiple comments and block users in one action
- ✅ **Job Queue Support**: Async processing for blocking operations
- ✅ **Single Request Pattern**: All HTTP calls use one `request()` method
- ✅ **Per-Account Tokens**: No global API keys - each account has its own credentials
- ✅ **Audit Trail**: Track blocked accounts with reasons and comment context

## Architecture

### API Client Flow

```
[Grandma's Browser]
         ↓
[Filament UI (Following → Posts → Comments)]
         ↓
[BlockUserJob (async queue)]
         ↓
[BlockedAccountService]
         ↓
[InstagramApiService (extends InstagramBaseClient)]
         ↓
[InstagramBaseClient (single request() method)]
         ↓
[HttpClientExceptionDecorator (error handling)]
         ↓
[ExternalClient (Laravel Http facade)]
         ↓
[Instagram Graph API]
```

### Core Principles

1. **Single `request()` Method**: All API calls use `request(method, account, endpoint, options)` - no `get()`/`post()` wrappers
2. **API as Data Source**: Filament pages fetch data directly from Instagram API (no database caching)
3. **Per-Account Authentication**: Each `Account` has its own access token
4. **Async Job Processing**: Blocking operations run in background via Laravel queues
5. **Consistent Code Style**: Looks like one person coded it in one day

## Requirements

- PHP 8.3+
- Composer
- Laravel 12
- SQLite (default) or MySQL/PostgreSQL
- Instagram Graph API access token (per account)

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/underdogg-forks/trollbegone.git
   cd trollbegone
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run migrations:**
   ```bash
   php artisan migrate
   ```

5. **Create admin user:**
   ```bash
   php artisan make:filament-user
   ```

6. **Run queue worker** (for blocking jobs):
   ```bash
   php artisan queue:work
   ```

## Usage

### For Grandma: Using the Admin Panel

1. **Login**: Access Filament admin at `http://localhost:8000/admin`

2. **View Your Accounts**: See all your connected Instagram accounts

3. **View Following**: Click "View Following" to see users you follow on Instagram

4. **Browse Posts**: Click on any user to see their posts

5. **Review Comments**: Click "View Comments" on any post to see all comments

6. **Block Users**: 
   - Click comments to select them
   - Click "Block Selected Users" button
   - Users will be blocked asynchronously via job queue

### For Developers: Managing Accounts Programmatically

Each Instagram account operates independently with its own access token:

```php
use App\Models\Account;

// Create a new account
$account = Account::create([
    'username' => 'myaccount',
    'instagram_id' => '123456789',
    'is_active' => true,
]);

// Set access token (guarded property)
$account->access_token = 'your_instagram_access_token';
$account->save();
```

### Blocking Users

```php
use App\Jobs\BlockUserJob;
use App\Models\Account;

$account = Account::find(1);

// Dispatch blocking job (async)
BlockUserJob::dispatch(
    account: $account,
    username: 'trolluser',
    reason: 'Spam comments',
    commentText: 'Buy followers now!'
);

// Or use service directly (sync)
$service = app(\App\Services\Instagram\BlockedAccountService::class);
$blocked = $service->blockAccount(
    account: $account,
    username: 'trolluser',
    reason: 'Spam',
    commentText: 'Offensive comment'
);
```

### Fetching Data from Instagram API

```php
use App\Services\Instagram\InstagramApiService;
use App\Models\Account;
use App\Enums\RequestMethod;

$account = Account::find(1);
$api = app(InstagramApiService::class);

// Get stories
$stories = $api->getStories($account);

// Get comments on a story
$comments = $api->getStoryComments($account, $storyId);

// Search for user
$userInfo = $api->getUserInfo($account, 'username');

// Block user
$api->blockUser($account, $instagramUserId);

// Use raw request() method for any endpoint
$response = $api->request(
    method: RequestMethod::GET,
    account: $account,
    endpoint: '/me/following',
    options: ['query' => ['fields' => 'id,username']]
);
```

## API Client Architecture

### The Single Request Pattern

All Instagram API calls flow through one method:

```php
abstract class InstagramBaseClient
{
    /**
     * Make authenticated requests to Instagram API.
     * 
     * @param RequestMethod|string $method HTTP method (GET, POST, PUT, DELETE)
     * @param Account $account Account with access token
     * @param string $endpoint API endpoint (e.g., '/me/stories')
     * @param array $options Request options (query, json, etc.)
     */
    protected function request(
        RequestMethod|string $method,
        Account $account,
        string $endpoint,
        array $options = []
    ): Response;
}
```

### Service Layer

**InstagramApiService**: High-level methods for common operations

```php
class InstagramApiService extends InstagramBaseClient
{
    public function getStories(Account $account): Collection
    {
        $response = $this->request(RequestMethod::GET, $account, '/me/stories');
        return collect($response->json('data', []));
    }
    
    public function blockUser(Account $account, string $userId): bool
    {
        try {
            $this->request(RequestMethod::POST, $account, '/me/blocked', [
                'json' => ['user_id' => $userId],
            ]);
            return true;
        } catch (\Exception $e) {
            \Log::warning('Block failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
```

**BlockedAccountService**: Business logic for blocking workflow

```php
class BlockedAccountService
{
    public function blockAccount(
        Account $account,
        string $username,
        ?string $reason = null,
        ?string $commentText = null
    ): BlockedAccount {
        // Search user, create DB record, call Instagram API
        return DB::transaction(function () use ($account, $username, $reason, $commentText) {
            $userInfo = $this->instagramApi->getUserInfo($account, $username);
            $blockedAccount = BlockedAccount::create([...]);
            $this->instagramApi->blockUser($account, $userInfo['id']);
            return $blockedAccount;
        });
    }
}
```

## Multi-Account Architecture

**CRITICAL**: This application supports **multiple Instagram accounts** with **concurrent users**.

### Why Per-Account Tokens?

❌ **No Global API Keys**
```php
// BAD - Don't do this
$token = config('services.instagram.api_key');
```

✅ **Per-Account Tokens**
```php
// GOOD - Each account has its own token
$account = \App\Models\Account::find(1);
$stories = app(\App\Services\Instagram\InstagramApiService::class)->getStories($account);
```

### Concurrent User Workflow

```
User A → Account A → Blocks user X with Account A's token
User B → Account B → Blocks user Y with Account B's token (simultaneously)
```

Both operations are **independent** and **thread-safe**.

## Instagram Graph API Setup

### Prerequisites

1. Facebook Developer account
2. Instagram Business or Creator account
3. Facebook App with Instagram Graph API access
4. Access token for each Instagram account

### Getting an Access Token

1. Create a Facebook App.
2. Add Instagram Graph API permissions (`instagram_basic`, `instagram_manage_comments`, `instagram_manage_insights`).
3. Configure `INSTAGRAM_CLIENT_ID`, `INSTAGRAM_CLIENT_SECRET`, and `INSTAGRAM_REDIRECT_URI` in `.env`.
4. In the app, open **Admin → Instagram Accounts** and click **Connect Instagram** (OAuth flow).
5. After callback, TrollBeGone stores the returned token in `instagram_accounts.access_token` (encrypted cast on `Account` model).

```php
$account->access_token = 'your_long_lived_token';
$account->save();
```

### Token Retrieval and Renewal in TrollBeGone

- **Retrieval for requests**: every Instagram request uses the `Account` instance passed to `InstagramBaseClient::request(...)`, which injects that account's `access_token`.
- **Renewal/rotation**: reconnecting the same Instagram account via OAuth updates the existing record (`updateOrCreate` in `InstagramOAuthController`) and replaces the stored `access_token`.
- **Verification in tests**: workflow tests assert token storage, token usage in request options, and that renewed tokens are used on subsequent API calls.

### API Endpoints Used

- `GET /me/stories` - Fetch stories
- `GET /{story_id}/comments` - Get story comments
- `POST /me/blocked` - Block a user
- `GET /search?q={username}&type=user` - Search users

## Database Schema

### `instagram_accounts`

```sql
CREATE TABLE instagram_accounts (
    id BIGINT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    instagram_id VARCHAR(255),
    access_token TEXT, -- Guarded, encrypted
    is_active BOOLEAN DEFAULT 1,
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### `blocked_accounts`

```sql
CREATE TABLE blocked_accounts (
    id BIGINT PRIMARY KEY,
    instagram_account_id BIGINT NOT NULL,
    blocked_username VARCHAR(255) NOT NULL,
    blocked_instagram_id VARCHAR(255),
    reason TEXT,
    comment_text TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (instagram_account_id) REFERENCES instagram_accounts(id)
);
```

**Note**: Deleting an `Account` does **NOT** cascade delete `BlockedAccount` records (audit trail preservation).

## Development

### Code Structure

```
app/
├── Services/
│   ├── Http/
│   │   ├── ExternalClient.php          # Laravel Http wrapper
│   │   ├── HttpExceptionHandler.php    # Exception decorator
│   │   └── HttpClientException.php     # Custom exception
│   └── Instagram/
│       ├── InstagramBaseClient.php     # Abstract base client
│       ├── InstagramApiService.php     # Instagram API service
│       └── BlockedAccountService.php   # Business logic
├── Models/
│   ├── Account.php
│   └── BlockedAccount.php
└── Filament/ (optional)
    └── Resources/
```

### Running Tests

```bash
# All tests
php artisan test

# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# With coverage
php artisan test --coverage
```

### Code Quality

```bash
# Format code (PSR-12)
./vendor/bin/pint

# Check formatting
./vendor/bin/pint --test
```

### Coding Standards

1. **Single `request()` method** - No `get()`/`post()` wrappers
2. **Constructor DI** - All dependencies injected
3. **Guard clauses** - Early returns, max nesting depth 2
4. **Type hints** - Full parameter and return type hints
5. **Per-account tokens** - No global credentials

## Security

### Best Practices

- ✅ Never commit `.env` or secrets
- ✅ Access tokens stored per account (guarded property)
- ✅ User-facing errors are generic
- ✅ Detailed errors logged server-side
- ✅ HTTPS required for OAuth callbacks

### Example: Error Handling

```php
try {
    $service->blockByUsername($account, 'trolluser', 'Spam');
} catch (\Exception $e) {
    // Log detailed error
    Log::error('Block failed', [
        'account' => $account->username,
        'error' => $e->getMessage(),
    ]);
    
    // Show generic message to user
    return response()->json(['error' => 'Unable to block user'], 500);
}
```

## Testing

### Test Pattern

All tests use the `it_...` naming convention and `#[Test]` attribute:

```php
use PHPUnit\Framework\Attributes\Test;

#[Test]
public function it_blocks_and_persists(): void
{
    $account = \App\Models\Account::factory()->create(['access_token' => 'token']);
    $api = \Mockery::mock(\App\Services\Instagram\InstagramApiService::class);
    
    $api->shouldReceive('getUserInfo')->once()->andReturn(['id' => '123']);
    $api->shouldReceive('blockUser')->once()->with($account, '123')->andReturnTrue();
    
    $service = app(\App\Services\Instagram\BlockedAccountService::class);
    $result = $service->blockAccount($account, 'troll', 'spam', 'bad comment');
    
    $this->assertDatabaseHas('blocked_accounts', [
        'instagram_account_id' => $account->id,
        'blocked_username' => 'troll',
    ]);
}
```

## Contributing

### Before Committing

Ensure:

- [ ] Single `request()` usage for all HTTP calls
- [ ] No global credentials; per-account tokens only
- [ ] Guard clauses on all public methods
- [ ] Max nesting depth ≤ 2
- [ ] Constructor DI everywhere
- [ ] Full parameter and return type hints
- [ ] Tests pass: `php artisan test`
- [ ] Code formatted: `./vendor/bin/pint`

## Resources

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Instagram Graph API](https://developers.facebook.com/docs/instagram-api)
- [Filament Admin](https://filamentphp.com/docs/4.x/admin) (if used)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)

## License

Open-sourced software licensed under the MIT license.

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/underdogg-forks/trollbegone).

---

**Remember**: ALWAYS use `request()`, ALWAYS use client-per-endpoint, ALWAYS use per-account tokens.
