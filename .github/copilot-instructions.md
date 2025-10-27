# GitHub Copilot Instructions for TrollBeGone

## Project Overview

TrollBeGone is a Laravel 12 application with Filament v4 for managing Instagram story comments and blocking unwanted accounts. The application provides Instagram account management, story monitoring, comment moderation, account blocking, and maintains a list of blocked accounts.

## Technology Stack

- **Framework**: Laravel 12 (PHP 8.3+)
- **Admin Panel**: Filament v4
- **Database**: MariaDB
- **Frontend**: Vite with TailwindCSS
- **Testing**: PHPUnit 11.5+
- **Code Quality**: Laravel Pint (PSR-12)
- **API Integration**: Instagram Graph API

## Core Development Philosophy

This application is designed to look like **one person coded it in a single day** with consistent patterns throughout. Our code follows three fundamental principles:

### 1. **SOLID Principles** (Always)
- **S**ingle Responsibility: Each class has one job and one reason to change
- **O**pen/Closed: Open for extension, closed for modification (use decorators, inheritance)
- **L**iskov Substitution: Subtypes must be substitutable for their base types
- **I**nterface Segregation: Small, focused interfaces over large ones
- **D**ependency Inversion: Depend on abstractions, not concretions (constructor injection)

### 2. **Dynamic & Flexible** (Always)
- Use modern PHP 8.3+ features (named parameters, constructor property promotion)
- Leverage Laravel's dynamic features (collections, facades, helpers)
- Type hint everything but embrace flexibility where needed
- Return flexible types (Collection, array, null, bool) based on context

### 3. **Early Returns & Guard Clauses** (Always)
- Check preconditions first and exit early
- Avoid deep nesting - flatten your code
- Fail fast with meaningful exceptions
- Use guard clauses at the start of methods
- One happy path through the method

## Architecture Patterns

### Layered Architecture

1. **HTTP Client Layer**
   - `ExternalClient`: Single request function using Laravel HTTP client
   - `HttpClientExceptionDecorator`: Wraps ExternalClient for consistent exception handling
   - Uses GuzzleHttp-compatible interface
   
   **Request Method Signature**:
   ```php
   public function request(
       RequestMethod|string $method,
       Account $account,
       string $endpoint,
       array $options = []
   ): Response
   ```
   
   Services call this method for all HTTP operations (GET, POST, etc.).

2. **Service Layer**
   - `InstagramBaseClient`: Abstract base class for Instagram API services
   - `InstagramApiService`: Handles all Instagram Graph API interactions
   - `BlockedAccountService`: Manages blocked account business logic

3. **Model Layer**
   - `Account`: Connected Instagram account with access tokens
   - `BlockedAccount`: Tracks blocked users with reasons
   - `User`: Filament admin users

4. **Presentation Layer**
   - Filament Resources for admin UI
   - Custom actions and table columns
   - Livewire components for interactive features

### Design Patterns in Use

- **Decorator Pattern**: `HttpClientExceptionDecorator` wraps the HTTP client for exception handling
- **Service Pattern**: Business logic separated into dedicated service classes
- **Repository Pattern**: Eloquent models act as repositories for data access
- **Factory Pattern**: Database factories for testing and seeding
- **Abstract Base Classes**: `InstagramBaseClient` provides shared functionality for API clients

### Architectural Principles

1. **Layered Architecture**: Clear separation between HTTP, Service, Model, and Presentation layers
2. **Dependency Injection**: All dependencies injected via constructor (never use `new` in business logic)
3. **Interface Segregation**: Small, focused services over monolithic classes
4. **Composition over Inheritance**: Prefer composing objects over deep inheritance chains
5. **Fail Fast**: Validate inputs early, throw exceptions for critical errors, return null/false for graceful degradation

## Code Style and Conventions

### PSR-12 Compliance

Always follow PSR-12 coding standards. The project uses Laravel Pint for automatic formatting:

```bash
./vendor/bin/pint
```

### Coding Style Essentials

#### 1. Early Returns & Guard Clauses (Critical!)

**ALWAYS** check preconditions first and return early. This is our most important pattern.

```php
// ✅ GOOD - Early returns with guard clauses
public function blockUser(Account $account, string $userId): bool
{
    // Guard clause - check preconditions first
    if (!$account->access_token) {
        throw new Exception("No access token available for account: {$account->username}");
    }

    try {
        $this->post($account, '/me/blocked', ['user_id' => $userId]);
        return true;
    } catch (\Exception $e) {
        // Early return on error
        return false;
    }
}

// ❌ BAD - Deep nesting, late checks
public function blockUser(Account $account, string $userId): bool
{
    if ($account->access_token) {
        try {
            $this->post($account, '/me/blocked', ['user_id' => $userId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    } else {
        throw new Exception("No access token available for account: {$account->username}");
    }
}
```

#### 2. SOLID in Practice

**Single Responsibility**: Each class does one thing

```php
// ✅ GOOD - Single responsibility
class InstagramApiService extends InstagramBaseClient
{
    // Only handles Instagram API calls
    public function getStories(Account $account): Collection { }
    public function blockUser(Account $account, string $userId): bool { }
}

class BlockedAccountService
{
    // Only handles blocking business logic
    public function blockAccount(Account $account, string $username): BlockedAccount { }
    public function isBlocked(Account $account, string $username): bool { }
}
```

**Dependency Inversion**: Inject dependencies, never instantiate in methods

```php
// ✅ GOOD - Constructor injection
public function __construct(
    protected InstagramApiService $instagramApi
) {}

public function blockAccount(Account $account, string $username): BlockedAccount
{
    $userInfo = $this->instagramApi->getUserInfo($account, $username);
    // ... rest of logic
}

// ❌ BAD - Direct instantiation
public function blockAccount(Account $account, string $username): BlockedAccount
{
    $instagramApi = new InstagramApiService(); // Never do this!
    // ...
}
```

**Open/Closed**: Extend behavior via inheritance or decoration

```php
// ✅ GOOD - Decorator pattern for extending behavior
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
            throw new HttpClientException(...);
        }
    }
}
```

#### 3. Dynamic & Modern PHP

**Use Constructor Property Promotion** (PHP 8.0+)

```php
// ✅ GOOD - Constructor property promotion
public function __construct(
    protected InstagramApiService $instagramApi,
    protected HttpClientExceptionDecorator $httpClient
) {}

// ❌ BAD - Old style
protected $instagramApi;
protected $httpClient;

public function __construct(InstagramApiService $instagramApi, HttpClientExceptionDecorator $httpClient)
{
    $this->instagramApi = $instagramApi;
    $this->httpClient = $httpClient;
}
```

**Use Named Parameters** (PHP 8.0+)

```php
// ✅ GOOD - Named parameters for clarity
$blockedAccount = BlockedAccount::create([
    'instagram_account_id' => $account->id,
    'blocked_username' => $username,
    'blocked_instagram_id' => $userInfo['id'] ?? null,
    'reason' => $reason,
    'comment_text' => $commentText,
]);

// Also good for complex method calls
throw new HttpClientException(
    message: $e->getMessage(),
    code: $e->response?->status() ?? 0,
    previous: $e
);
```

**Leverage Collections**

```php
// ✅ GOOD - Return collections for flexibility
public function getStories(Account $account): Collection
{
    $response = $this->get($account, '/me/stories');
    return collect($response->json('data', []));
}
```

#### 4. Type Hinting & Return Types

**ALWAYS** type hint parameters and return types:

```php
// ✅ GOOD - Full type hints
public function blockUser(Account $account, string $userId): bool
public function getUserInfo(Account $account, string $username): ?array
public function getStories(Account $account): Collection

// ❌ BAD - No type hints
public function blockUser($account, $userId)
public function getUserInfo($account, $username)
```

**Use Nullable Types When Appropriate**

```php
// ✅ GOOD - Nullable return for optional data
public function getUserInfo(Account $account, string $username): ?array
{
    try {
        $response = $this->get($account, '/search', ['q' => $username, 'type' => 'user']);
        $users = $response->json('data', []);
        return $users[0] ?? null; // Early return if no users
    } catch (\Exception $e) {
        return null; // Graceful degradation
    }
}
```

#### 5. Exception Handling Strategy

**Critical Operations**: Throw exceptions

```php
// ✅ GOOD - Throw exception for critical operation
protected function ensureAccessToken(Account $account): void
{
    if (!$account->access_token) {
        throw new Exception("No access token available for account: {$account->username}");
    }
}
```

**Non-Critical Operations**: Return null/false for graceful degradation

```php
// ✅ GOOD - Return false for non-critical failure
public function blockUser(Account $account, string $userId): bool
{
    try {
        $this->post($account, '/me/blocked', ['user_id' => $userId]);
        return true;
    } catch (\Exception $e) {
        // Silently fail and return false
        return false;
    }
}
```

**Transaction Wrapping**: Use DB transactions for multi-step operations

```php
// ✅ GOOD - Transaction for atomicity
public function blockAccount(Account $account, string $username): BlockedAccount
{
    return DB::transaction(function () use ($account, $username, $reason, $commentText) {
        // Multiple database operations protected by transaction
        $userInfo = $this->instagramApi->getUserInfo($account, $username);
        $blockedAccount = BlockedAccount::create([...]);
        $this->instagramApi->blockUser($account, $userInfo['id']);
        return $blockedAccount;
    });
}
```

### Naming Conventions

- **Classes**: PascalCase (e.g., `InstagramApiService`)
- **Methods**: camelCase (e.g., `blockUser`, `getStories`)
- **Variables**: camelCase (e.g., `$account`, `$userId`)
- **Constants**: SCREAMING_SNAKE_CASE (e.g., `API_VERSION`)
- **Database tables**: snake_case plural (e.g., `instagram_accounts`, `blocked_accounts`)
- **Database columns**: snake_case (e.g., `instagram_id`, `access_token`)

### Laravel Best Practices

1. **Eloquent Models**
   - Always define `$fillable` or `$guarded` properties
   - Use proper `$casts` for type safety
   - Document relationships with PHPDoc `@return` tags
   - Include comprehensive PHPDoc blocks with `@property` tags

2. **Service Classes**
   - One responsibility per service
   - Inject dependencies via constructor using property promotion
   - Return types should be type-hinted (Collection, array, ?array, bool, ?Model)
   - Use exceptions for error handling in critical paths
   - Return `false` or `null` for graceful degradation in non-critical operations
   - **ALWAYS** use guard clauses and early returns

3. **Controllers**
   - Keep thin - delegate to services immediately
   - Use form requests for validation
   - Return appropriate HTTP status codes
   - Use early returns for error conditions
   - Handle exceptions at the controller boundary

4. **Routes**
   - Use named routes for all endpoints
   - Group related routes with prefixes and middleware
   - Follow RESTful conventions

### Filament Conventions

1. **Resources**
   - Organize by feature in subdirectories (e.g., `BlockedAccounts/`, `Accounts/`)
   - Separate concerns: Tables, Forms, Pages
   - Use static methods for configuration

2. **Actions**
   - Custom actions in separate classes when complex
   - Use modals for confirmation dialogs
   - Provide clear success/error notifications

3. **Tables**
   - Define columns in dedicated Table classes
   - Use filters for common queries
   - Add bulk actions where appropriate

## Documentation Standards

### PHPDoc Requirements

All classes and public methods must have comprehensive PHPDoc blocks:

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
 *
 * @param Account $account The Instagram account
 * @param string $userId The Instagram user ID to block
 * @return bool True if successful, false otherwise
 * @throws \Exception If no access token is available
 */
public function blockUser(Account $account, string $userId): bool
{
    // Implementation
}
```

### Model Documentation

Models should include:
- Class-level PHPDoc with property annotations
- Relationship return types
- Method descriptions

```php
/**
 * Instagram Account Model
 *
 * @property int $id
 * @property string $username
 * @property string|null $instagram_id
 * @property bool $is_active
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BlockedAccount> $blockedAccounts
 */
class Account extends Model
```

## Testing Requirements

### Test Coverage Expectations

- **Unit Tests**: All service classes and HTTP clients
- **Feature Tests**: End-to-end workflows and database interactions
- **Integration Tests**: External API interactions (use fixtures)

### Testing Standards

1. **Unit Tests** (`tests/Unit/`)
   - Mock external dependencies using Mockery
   - Test single units of code in isolation
   - Use `#region` comments for Arrange/Act/Assert pattern
   - Test happy paths and edge cases
   - Minimum 13-15 tests per service class
   - Use early returns in test setup

2. **Feature Tests** (`tests/Feature/`)
   - Use `RefreshDatabase` trait
   - Test actual database interactions
   - Validate complete workflows
   - Test relationships and cascades
   - Use factories for test data

3. **Factories** (`database/factories/`)
   - Provide realistic test data
   - Include factory states for variations
   - Support flexible configuration

### Test Structure Pattern

**ALWAYS** use the #region pattern for test organization:

```php
#[Test]
public function blocking_account_creates_database_record(): void
{
    /** #region Arrange */
    $account = Account::create([
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

### Test Naming

```php
// Pattern: test_method_name_scenario_expected_behavior
public function test_block_account_creates_blocked_account_record_with_user_info()
public function test_get_stories_returns_empty_collection_when_no_stories()
public function test_is_blocked_returns_false_for_non_blocked_users()
```

### Running Tests

```bash
# All tests
php artisan test

# Specific suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# With coverage
php artisan test --coverage

# Specific test
php artisan test --filter test_block_account_creates_blocked_account_record
```

## Security Best Practices

### Sensitive Data

1. **Never commit secrets**
   - Use `.env` for all credentials
   - Add sensitive keys to `.env.example` with placeholder values
   - Use `config()` helper to access environment variables

2. **Access Tokens**
   - Store encrypted in database when possible
   - Validate token presence before API calls
   - Handle token expiration gracefully

3. **OAuth Flow**
   - Use HTTPS in production
   - Validate redirect URIs
   - Implement CSRF protection

### Exception Handling

**Critical Path Operations** - Throw exceptions:
```php
// Configuration errors, missing dependencies, data integrity issues
protected function ensureAccessToken(Account $account): void
{
    if (!$account->access_token) {
        throw new Exception("No access token available for account: {$account->username}");
    }
}
```

**Non-Critical Operations** - Return null/false:
```php
// External API calls, optional features, search operations
public function getUserInfo(Account $account, string $username): ?array
{
    try {
        $response = $this->get($account, '/search', ['q' => $username, 'type' => 'user']);
        $users = $response->json('data', []);
        return $users[0] ?? null;
    } catch (\Exception $e) {
        // Silently fail and return null - user search is non-critical
        return null;
    }
}
```

**Wrap External Exceptions** - Custom exceptions for external calls:
```php
// ✅ GOOD - Wrap and transform exceptions
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
```

**User-Facing Code** - Never expose internal errors:
```php
// ✅ GOOD - User-friendly error messages
try {
    $blockedAccountService->blockAccount($account, $username);
    
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

### Input Validation

1. **Always validate user input**
   - Use Form Requests for complex validation
   - Validate at the controller level
   - Sanitize data before database operations

2. **SQL Injection Prevention**
   - Use Eloquent ORM or query builder
   - Never concatenate user input in queries
   - Use parameter binding

## Instagram Graph API Integration

### API Endpoints Used

- `GET /me/stories` - Fetch stories
- `GET /{story_id}/comments` - Get story comments
- `POST /me/blocked` - Block a user
- `GET /search?q={username}&type=user` - Search for users

### Authentication

- Uses OAuth 2.0 via Laravel Socialite
- Access tokens stored per Instagram account
- Token validation before each request

### Error Handling

- API failures should not crash the application
- Return `false` or `null` for non-critical operations
- Throw exceptions for critical path operations
- Log all API errors for debugging

## Common Tasks

### Adding a New Instagram API Method

1. Add method to `InstagramApiService`
2. Extend `InstagramBaseClient` for authenticated requests
3. Include comprehensive PHPDoc with endpoint details
4. Add unit tests with mocked responses
5. Add integration tests with fixtures
6. Handle exceptions gracefully

### Creating a New Filament Resource

1. Create resource directory: `app/Filament/Resources/ResourceName/`
2. Add resource class: `ResourceNameResource.php`
3. Add table configuration: `Tables/ResourceNameTable.php`
4. Add form schema: `Schemas/ResourceNameForm.php`
5. Add pages: `Pages/ListResourceName.php`, `Pages/CreateResourceName.php`, etc.

### Adding a New Service

1. Create service class in `app/Services/`
2. Add constructor dependency injection
3. Include comprehensive PHPDoc
4. Write unit tests
5. Write feature tests for workflows
6. Register in service provider if needed

### Database Changes

1. Create migration: `php artisan make:migration create_table_name`
2. Update model with new fillable attributes
3. Update model PHPDoc with new properties
4. Create/update factory
5. Run migration: `php artisan migrate`
6. Update tests

## Code Quality Tools

### Laravel Pint (Code Formatting)

```bash
# Format all files
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test
```

### PHPUnit (Testing)

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage
```

## Development Workflow

1. **Start Development Server**
   ```bash
   php artisan serve
   ```

2. **Run Queue Worker** (if using queues)
   ```bash
   php artisan queue:work
   ```

3. **Watch Assets**
   ```bash
   npm run dev
   ```

4. **Run All Services** (using composer script)
   ```bash
   composer dev
   ```

## Git Workflow

### Commit Messages

Follow conventional commits format:

```
feat: add user blocking functionality
fix: resolve token validation issue
docs: update API documentation
test: add integration tests for stories
refactor: simplify HTTP client interface
```

### Branch Naming

- `feature/description` - New features
- `fix/description` - Bug fixes
- `refactor/description` - Code refactoring
- `docs/description` - Documentation updates

## Helpful Commands

### Artisan Commands

```bash
# Create Filament user
php artisan make:filament-user

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate
php artisan migrate:fresh --seed

# Generate key
php artisan key:generate
```

### Composer Commands

```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Run tests
composer test

# Run dev servers
composer dev
```

## Performance Considerations

1. **Database Queries**
   - Use eager loading to prevent N+1 queries
   - Add indexes to frequently queried columns
   - Use pagination for large datasets

2. **API Calls**
   - Cache responses when appropriate
   - Implement rate limiting
   - Use background jobs for heavy operations

3. **Asset Optimization**
   - Use Vite for bundling
   - Minify production assets
   - Lazy load heavy components

## Debugging Tips

1. **Use Laravel Telescope** (if installed)
   - Monitor requests, queries, and exceptions
   - Track API calls and performance

2. **Log Extensively**
   - Use `Log::info()`, `Log::error()` for debugging
   - Include context in log messages
   - Check `storage/logs/laravel.log`

3. **Use `dd()` and `dump()`**
   - Quick debugging in development
   - Remove before committing

4. **Tinker for Testing**
   ```bash
   php artisan tinker
   ```

## Important Notes

- **OAuth Credentials**: Never commit `.env` file or expose API credentials
- **Database**: Default is SQLite; connection configured in `config/database.php`
- **Access Tokens**: Instagram access tokens expire; implement refresh logic
- **API Rate Limits**: Instagram has rate limits; handle 429 responses
- **HTTPS Required**: OAuth callback requires HTTPS in production

## Resources

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Filament Documentation](https://filamentphp.com/docs/4.x/admin)
- [Instagram Graph API](https://developers.facebook.com/docs/instagram-api)
- [Laravel Socialite](https://laravel.com/docs/12.x/socialite)
- [PSR-12 Style Guide](https://www.php-fig.org/psr/psr-12/)

## When Writing Code

### Golden Rules (Non-Negotiable)

1. **ALWAYS** use early returns and guard clauses - never nest deeply
2. **ALWAYS** follow SOLID principles - single responsibility, dependency injection
3. **ALWAYS** use constructor property promotion for dependencies
4. **ALWAYS** type hint parameters and return types (including nullable types)
5. **ALWAYS** follow PSR-12 standards
6. **ALWAYS** include comprehensive PHPDoc with API examples for service methods
7. **ALWAYS** write tests for new features using #region pattern
8. **ALWAYS** handle exceptions gracefully (throw for critical, return null/false for non-critical)
9. **NEVER** commit sensitive data or credentials
10. **NEVER** expose internal errors to end users
11. **NEVER** use raw SQL queries - use Eloquent/Query Builder
12. **NEVER** instantiate dependencies with `new` in business logic - use injection
13. **NEVER** nest conditionals more than 2 levels deep - use early returns instead
14. **PREFER** small, focused methods over large complex ones (max 20 lines)
15. **PREFER** existing Laravel/Filament patterns over custom solutions
16. **PREFER** composition over inheritance
17. **PREFER** immutability - avoid changing passed objects when possible

### Code Quality Checklist

Before committing any code, verify:

- [ ] All methods have guard clauses and early returns
- [ ] No nesting deeper than 2 levels
- [ ] All dependencies injected via constructor
- [ ] All parameters and return types are type-hinted
- [ ] Comprehensive PHPDoc on all public methods
- [ ] Each class has a single, clear responsibility
- [ ] Exceptions thrown for critical errors, null/false returned for non-critical
- [ ] Tests written with #region Arrange/Act/Assert pattern
- [ ] Code formatted with `./vendor/bin/pint`
- [ ] All tests pass with `php artisan test`

### Example: The Perfect Method

```php
/**
 * Block a user and record it in the database.
 *
 * This method searches for the user on Instagram, creates a local record,
 * and calls the Instagram API to block them. Returns the created record
 * or throws an exception if the operation fails.
 *
 * @param Account $account The Instagram account performing the block
 * @param string $username The username to block
 * @param string|null $reason Optional reason for blocking
 * @param string|null $commentText Optional comment that triggered the block
 * @return BlockedAccount The created blocked account record
 *
 * @throws \Exception If there's an error during the process
 */
public function blockAccount(
    Account $account,
    string $username,
    ?string $reason = null,
    ?string $commentText = null
): BlockedAccount {
    // Guard clause - validate account has required data
    if (!$account->id) {
        throw new \Exception('Account must be saved before blocking users');
    }

    return DB::transaction(function () use ($account, $username, $reason, $commentText) {
        // Try to get user info, but don't fail if unavailable
        try {
            $userInfo = $this->instagramApi->getUserInfo($account, $username);
        } catch (\Exception $e) {
            $userInfo = null; // Early assignment on error
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
            try {
                $this->instagramApi->blockUser($account, $userInfo['id']);
            } catch (\Exception $e) {
                Log::warning('Instagram block failed', [
                    'account' => $account->username,
                    'target' => $username,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $blockedAccount;
    });
}
```

This method demonstrates:
- ✅ Guard clause at the start (validate account)
- ✅ Early assignment on error (userInfo = null)
- ✅ Type hints on all parameters and return
- ✅ Comprehensive PHPDoc
- ✅ Named parameters for clarity
- ✅ Nullable types (?string)
- ✅ DB transaction for atomicity
- ✅ Graceful error handling (try-catch but continue)
- ✅ Single responsibility (block and record)
- ✅ No deep nesting
