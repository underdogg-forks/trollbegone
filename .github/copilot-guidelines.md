# TrollBeGone Development Guidelines (GitHub Copilot)

## Scope

TrollBeGone is an API-only Laravel application that consumes the Instagram Graph API to read posts, fetch comments, and block offending users. It implements the **Advanced API Client** pattern with **client-per-endpoint** classes over a **BaseClient**, routed through a decorated **ExternalClient**. **All HTTP calls use a single `request()` method** (no `get()` / `post()` wrappers).

---

## Core Architecture

### 1) Advanced API Client Flow (Required)

```
[Service Layer]
↓
[Endpoint Client] (extends BaseClient)
↓
[BaseClient] → [HttpExceptionHandler] → [ExternalClient] → [Laravel Http]
```

- **Single entrypoint:** `request(method, url, options)` only.
- **Decoration:** Exceptions handled in `HttpExceptionHandler`.
- **No direct Http calls** in services or models.

### 2) Client-Per-Endpoint (Required)

- One class per endpoint group.
- One public method per concrete endpoint.
- No monolithic "doEverything" clients.

### 3) Multi-Account, Multi-Tenant (Required)

- No global or config-based API keys.
- Per-account tokens on `Account`.
- All operations require an `Account` instance.
- Concurrent-safe for many users.

---

## HTTP Client Layer

```php
abstract class BaseClient
{
    public function __construct(
        protected HttpExceptionHandler $http
    ) {}

    protected function request(string $method, string $url, array $options = []): \Illuminate\Http\Client\Response
    {
        return $this->http->request($method, $url, $options);
    }
}
```

```php
class HttpExceptionHandler
{
    public function __construct(
        protected ExternalClient $client
    ) {}

    public function request(string $method, string $url, array $options = []): \Illuminate\Http\Client\Response
    {
        $response = $this->client->request($method, $url, $options);
        $response->throw();
        return $response;
    }
}
```

```php
class ExternalClient
{
    public function request(string $method, string $url, array $options = []): \Illuminate\Http\Client\Response
    {
        return \Illuminate\Support\Facades\Http::send($method, $url, $options);
    }
}
```

---

## Instagram Clients

```php
abstract class InstagramBaseClient extends BaseClient
{
    protected const BASE_URI = 'https://graph.instagram.com';

    protected function withToken(Account $account, array $options = []): array
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

```php
class InstagramStoriesClient extends InstagramBaseClient
{
    public function list(Account $account): \Illuminate\Support\Collection
    {
        $opts = $this->withToken($account);
        $res = $this->request('GET', self::BASE_URI . '/me/stories', $opts);
        return collect($res->json('data', []));
    }
}
```

```php
class InstagramModerationClient extends InstagramBaseClient
{
    public function block(Account $account, string $instagramUserId): bool
    {
        $opts = $this->withToken($account, ['query' => ['user_id' => $instagramUserId]]);
        $this->request('POST', self::BASE_URI . '/me/blocked', $opts);
        return true;
    }
}
```

---

## Service Layer

* Pure orchestration and business rules.
* Delegates outbound calls to endpoint clients.
* Uses early returns and guard clauses.

```php
class BlockedAccountService
{
    public function __construct(
        protected InstagramModerationClient $moderation,
        protected InstagramUsersClient $users
    ) {}

    public function blockByUsername(
        Account $account,
        string $username,
        ?string $reason = null,
        ?string $commentText = null
    ): BlockedAccount {
        if (!$account->getKey()) {
            throw new \RuntimeException('Account must be persisted');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($account, $username, $reason, $commentText) {
            $user = $this->users->findFirst($account, $username);

            $blocked = BlockedAccount::create([
                'instagram_account_id' => $account->getKey(),
                'blocked_username' => $username,
                'blocked_instagram_id' => $user['id'] ?? null,
                'reason' => $reason,
                'comment_text' => $commentText,
            ]);

            if (!empty($user['id'])) {
                $this->moderation->block($account, $user['id']);
            }

            return $blocked;
        });
    }
}
```

---

## Models

```php
class Account extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'username',
        'instagram_id',
        'is_active',
        'last_synced_at',
    ];

    protected $guarded = [
        'access_token',
    ];

    public function blockedAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BlockedAccount::class);
    }
}
```

```php
class BlockedAccount extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'instagram_account_id',
        'blocked_username',
        'blocked_instagram_id',
        'reason',
        'comment_text',
    ];

    public function instagramAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
```

Note: Deleting an `Account` does not cascade delete `BlockedAccount` to preserve audit history.

---

## Coding Standards

* Constructor injection for all dependencies.
* Single Responsibility per class.
* Early returns and guard clauses.
* Maximum nesting depth: 2.
* Public methods fully type-hinted.
* No direct instantiation of HTTP clients in services.
* No `get()` / `post()` helpers; always `request()`.

---

## Exceptions

**Throw** on:

* Missing configuration
* Missing account token
* Data integrity violations
* Authentication failures

**Return null/false** on:

* Optional lookups
* Non-critical external calls

---

## Security

* Never commit secrets.
* Tokens stored per account.
* User-facing errors must be generic.
* Detailed errors logged server-side.

---

## Testing

* Unit tests for endpoint clients and services.
* Feature tests for flows with database.
* Integration tests use fixtures for external calls.
* Test functions use `it_...` snake_case and `#[Test]`.

Pattern:

```php
#[Test]
public function it_blocks_and_persists(): void
{
    $account = Account::factory()->create(['access_token' => 'x']);
    $stories = \Mockery::mock(InstagramStoriesClient::class);
    $mod = \Mockery::mock(InstagramModerationClient::class);
    $users = \Mockery::mock(InstagramUsersClient::class);

    $users->shouldReceive('findFirst')->once()->andReturn(['id' => '123']);
    $mod->shouldReceive('block')->once()->withArgs(fn($a, $id) => $id === '123')->andReturnTrue();

    $svc = new BlockedAccountService($mod, $users);
    $svc->blockByUsername($account, 'spammer', 'spam', 'bad words');

    $this->assertDatabaseHas('blocked_accounts', [
        'instagram_account_id' => $account->getKey(),
        'blocked_username' => 'spammer',
        'blocked_instagram_id' => '123',
    ]);
}
```

---

## Filament (If Used)

* Actions delegate to services only.
* Show success/error notifications.
* No business logic in resources or actions.

---

## Code Quality Checklist

* [ ] Single `request()` usage for all HTTP calls
* [ ] No global credentials; per-account tokens only
* [ ] Guard clauses on all public methods
* [ ] Max nesting depth ≤ 2
* [ ] Constructor DI everywhere
* [ ] Full parameter and return type hints
* [ ] SRP respected
* [ ] Exceptions for critical failures; null/false for optional paths
* [ ] Tests follow `it_` + `#[Test]`
* [ ] `./vendor/bin/pint` passing
* [ ] `php artisan test` passing

---

## Anti-Patterns (Do Not Commit)

```php
// No global tokens via config()
```

```php
// No new InstagramModerationClient() inside services
```

```php
// No doEverything($method, $url, $payload)
```

```php
// No get()/post() wrappers; use request()
```

---

## Future Work

* OAuth connect/refresh flows
* Rate limit handling with backoff and retry
* Caching read-heavy endpoints by account
* Metrics, alerting, and dead-letter strategies
