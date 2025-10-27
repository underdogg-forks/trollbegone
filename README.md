# TrollBeGone

A Laravel 12 API-only application for managing Instagram story comments and blocking unwanted accounts via the Instagram Graph API.

## 🎯 Overview

TrollBeGone is built on an **Advanced API Client** architecture with **client-per-endpoint** classes, enabling precise Instagram Graph API integration for reading posts, fetching comments, and blocking offending users.

### Key Features

- ✅ **Multi-Account Support**: Manage unlimited Instagram accounts independently
- ✅ **Client-Per-Endpoint**: Focused API clients (Stories, Moderation, Users)
- ✅ **Single Request Pattern**: All HTTP calls use one `request()` method
- ✅ **Multi-Tenant Safe**: 10+ users can operate concurrently without conflicts
- ✅ **Per-Account Tokens**: No global API keys - each account has its own credentials
- ✅ **Audit Trail**: Track blocked accounts with reasons and context

## Architecture

### Advanced API Client Flow

```
[Service Layer (BlockedAccountService)]
          ↓
[Endpoint Clients (StoriesClient, ModerationClient)]
          ↓
[InstagramBaseClient (withToken)]
          ↓
[HttpExceptionHandler (wraps exceptions)]
          ↓
[ExternalClient (Laravel Http facade)]
          ↓
[Instagram Graph API]
```

### Core Principles

1. **Single `request()` Method**: No `get()`/`post()` wrappers - only `request(method, url, options)`
2. **Client-Per-Endpoint**: One class per API endpoint group (e.g., `InstagramStoriesClient`)
3. **Per-Account Authentication**: Each `InstagramAccount` has its own access token
4. **Concurrent Safety**: Multiple users can manage different accounts simultaneously

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

5. **Create admin user (if using Filament):**
   ```bash
   php artisan make:filament-user
   ```

## Usage

### Start Development Server

```bash
php artisan serve
```

Access at `http://localhost:8000`

### Managing Instagram Accounts

Each Instagram account operates independently with its own access token:

```php
use App\Models\InstagramAccount;

// Create a new Instagram account
$account = InstagramAccount::create([
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
use App\Services\Instagram\BlockedAccountService;
use App\Models\InstagramAccount;

$account = InstagramAccount::find(1);
$service = app(BlockedAccountService::class);

// Block a user by username
$blocked = $service->blockByUsername(
    account: $account,
    username: 'trolluser',
    reason: 'Spam comments',
    commentText: 'Buy followers now!'
);
```

### Fetching Stories

```php
use App\Services\Instagram\InstagramStoriesClient;
use App\Models\InstagramAccount;

$account = InstagramAccount::find(1);
$storiesClient = app(InstagramStoriesClient::class);

// Get all stories for this account
$stories = $storiesClient->list($account);

foreach ($stories as $story) {
    echo "Story ID: {$story['id']}\n";
}
```

## API Client Architecture

### Base Client Pattern

All Instagram API calls flow through a base client:

```php
abstract class InstagramBaseClient extends BaseClient
{
    protected const BASE_URI = 'https://graph.instagram.com';

    protected function withToken(InstagramAccount $account, array $options = []): array
    {
        if (empty($account->access_token)) {
            throw new \RuntimeException('Missing access token');
        }

        $options['query'] = array_merge(
            ['access_token' => $account->access_token],
            $options['query'] ?? []
        );

        return $options;
    }
}
```

### Endpoint Clients

Each endpoint group has its own dedicated client:

**InstagramStoriesClient**: Fetches stories
```php
public function list(InstagramAccount $account): Collection
{
    $opts = $this->withToken($account);
    $res = $this->request('GET', self::BASE_URI . '/me/stories', $opts);
    return collect($res->json('data', []));
}
```

**InstagramModerationClient**: Blocks users
```php
public function block(InstagramAccount $account, string $instagramUserId): bool
{
    $opts = $this->withToken($account, ['query' => ['user_id' => $instagramUserId]]);
    $this->request('POST', self::BASE_URI . '/me/blocked', $opts);
    return true;
}
```

**InstagramUsersClient**: Searches for users
```php
public function findFirst(InstagramAccount $account, string $username): ?array
{
    $opts = $this->withToken($account, ['query' => ['q' => $username, 'type' => 'user']]);
    $res = $this->request('GET', self::BASE_URI . '/search', $opts);
    $users = $res->json('data', []);
    return $users[0] ?? null;
}
```

### HTTP Exception Handling

The `HttpExceptionHandler` wraps all HTTP calls and ensures exceptions are thrown:

```php
class HttpExceptionHandler
{
    public function __construct(protected ExternalClient $client) {}

    public function request(string $method, string $url, array $options = []): Response
    {
        $response = $this->client->request($method, $url, $options);
        $response->throw();
        return $response;
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
$account = InstagramAccount::find(1);
$stories = $storiesClient->list($account); // Uses $account->access_token
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

1. Create a Facebook App
2. Add Instagram Graph API permissions
3. Generate User Access Token via OAuth flow
4. Store token in `InstagramAccount` model

```php
$account->access_token = 'your_long_lived_token';
$account->save();
```

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

**Note**: Deleting an `InstagramAccount` does **NOT** cascade delete `BlockedAccount` records (audit trail preservation).

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
│       ├── InstagramStoriesClient.php  # Stories endpoint
│       ├── InstagramModerationClient.php # Moderation endpoint
│       ├── InstagramUsersClient.php    # Users endpoint
│       └── BlockedAccountService.php   # Business logic
├── Models/
│   ├── InstagramAccount.php
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
    $account = InstagramAccount::factory()->create(['access_token' => 'token']);
    $mod = \Mockery::mock(InstagramModerationClient::class);
    $users = \Mockery::mock(InstagramUsersClient::class);
    
    $users->shouldReceive('findFirst')->once()->andReturn(['id' => '123']);
    $mod->shouldReceive('block')->once()->andReturnTrue();
    
    $service = new BlockedAccountService($mod, $users);
    $result = $service->blockByUsername($account, 'troll', 'spam', 'bad comment');
    
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
