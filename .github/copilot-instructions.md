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

## Architecture Patterns

### Layered Architecture

1. **HTTP Client Layer**
   - `ExternalClient`: Single request function using Laravel HTTP client
   - `HttpClientExceptionDecorator`: Wraps ExternalClient for consistent exception handling
   - Uses GuzzleHttp-compatible interface

2. **Service Layer**
   - `InstagramBaseClient`: Abstract base class for Instagram API services
   - `InstagramApiService`: Handles all Instagram Graph API interactions
   - `BlockedAccountService`: Manages blocked account business logic

3. **Model Layer**
   - `InstagramAccount`: Connected Instagram account with access tokens
   - `BlockedAccount`: Tracks blocked users with reasons
   - `User`: Filament admin users

4. **Presentation Layer**
   - Filament Resources for admin UI
   - Custom actions and table columns
   - Livewire components for interactive features

### Design Patterns in Use

- **Decorator Pattern**: `HttpClientExceptionDecorator` wraps the HTTP client
- **Service Pattern**: Business logic separated into service classes
- **Repository Pattern**: Eloquent models act as repositories
- **Factory Pattern**: Database factories for testing

## Code Style and Conventions

### PSR-12 Compliance

Always follow PSR-12 coding standards. The project uses Laravel Pint for automatic formatting:

```bash
./vendor/bin/pint
```

### Naming Conventions

- **Classes**: PascalCase (e.g., `InstagramApiService`)
- **Methods**: camelCase (e.g., `blockUser`, `getStories`)
- **Variables**: camelCase (e.g., `$instagramAccount`, `$userId`)
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
   - Inject dependencies via constructor
   - Return types should be type-hinted
   - Use exceptions for error handling in critical paths
   - Return `false` or `null` for graceful degradation

3. **Controllers**
   - Keep thin - delegate to services
   - Use form requests for validation
   - Return appropriate HTTP status codes

4. **Routes**
   - Use named routes for all endpoints
   - Group related routes with prefixes and middleware
   - Follow RESTful conventions

### Filament Conventions

1. **Resources**
   - Organize by feature in subdirectories (e.g., `BlockedAccounts/`, `InstagramAccounts/`)
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
 * @param InstagramAccount $account The Instagram account
 * @param string $userId The Instagram user ID to block
 * @return bool True if successful, false otherwise
 * @throws \Exception If no access token is available
 */
public function blockUser(InstagramAccount $account, string $userId): bool
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
class InstagramAccount extends Model
```

## Testing Requirements

### Test Coverage Expectations

- **Unit Tests**: All service classes and HTTP clients
- **Feature Tests**: End-to-end workflows and database interactions
- **Integration Tests**: External API interactions (use fixtures)

### Testing Standards

1. **Unit Tests** (`tests/Unit/`)
   - Mock external dependencies
   - Test single units of code
   - Use Mockery for mocking
   - Test happy paths and edge cases
   - Minimum 13-15 tests per service class

2. **Feature Tests** (`tests/Feature/`)
   - Use `RefreshDatabase` trait
   - Test actual database interactions
   - Validate complete workflows
   - Test relationships and cascades

3. **Factories** (`database/factories/`)
   - Provide realistic test data
   - Include factory states for variations
   - Support flexible configuration

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

1. **Wrap external API exceptions**
   ```php
   try {
       $response = $this->client->request('GET', $url);
       return $response->json();
   } catch (\Exception $e) {
       // Log but don't expose sensitive data
       Log::error('API call failed', ['error' => $e->getMessage()]);
       return false; // or null for graceful degradation
   }
   ```

2. **Don't expose internal errors to users**
   - Use custom exception messages
   - Log detailed errors for debugging
   - Return user-friendly messages

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

1. **Always** follow PSR-12 standards
2. **Always** include comprehensive PHPDoc
3. **Always** write tests for new features
4. **Always** handle exceptions gracefully
5. **Always** use type hints for parameters and return types
6. **Never** commit sensitive data or credentials
7. **Never** expose internal errors to end users
8. **Never** use raw SQL queries - use Eloquent/Query Builder
9. **Prefer** existing Laravel/Filament patterns over custom solutions
10. **Prefer** small, focused methods over large complex ones
