# TrollBeGone

A Laravel 12 application with Filament v4 for managing Instagram story comments and blocking unwanted accounts.

## 🎯 Core Architecture

TrollBeGone is built on a **multi-account, multi-tenant architecture** that supports:

- ✅ **Multiple Instagram Accounts**: Each account operates independently with its own access token
- ✅ **Concurrent Users**: 10+ users can manage their accounts simultaneously without conflicts
- ✅ **Per-Account Authentication**: No global API keys - each account has its own Instagram Graph API token
- ✅ **BaseClient Pattern**: Consistent API integration with InstagramBaseClient handling URL, version, endpoints, and authentication
- ✅ **Specific Endpoint Clients**: Focused service classes (InstagramApiService) with one method per endpoint

## Features

- **Instagram Account Management**: Track unlimited Instagram accounts, each with independent authentication
- **Story Monitoring**: View stories from any connected Instagram account
- **Comment Moderation**: Review comments on stories per account
- **Account Blocking**: Block users directly from comment review using account-specific tokens
- **Blocked Account Tracking**: Maintain a list of blocked accounts with reasons (per Instagram account)

## Requirements

- PHP 8.3+
- Composer
- SQLite (default) or other database
- Instagram Graph API access token

## Installation

1. Clone the repository:
```bash
git clone https://github.com/underdogg-forks/trollbegone.git
cd trollbegone
```

2. Install dependencies:
```bash
composer install
```

3. Copy the environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Run migrations:
```bash
php artisan migrate
```

6. Create an admin user:
```bash
php artisan make:filament-user
```

## Usage

1. Start the development server:
```bash
php artisan serve
```

2. Access Filament admin panel at `http://localhost:8000/admin`

3. Add Instagram accounts with their access tokens in the "Instagram Accounts" section

4. View stories and comments by clicking "View Stories" on an account

5. Block users directly from the comments view

## Architecture

TrollBeGone follows three fundamental architectural principles:

### 1. BaseClient Pattern (ALWAYS)

Every external API integration uses a BaseClient that provides:
- **API Base URL**: Centralized endpoint configuration (`https://graph.instagram.com`)
- **API Version**: Version management for the external service
- **Endpoint Management**: String-based endpoint handling (`/me/stories`, `/{story_id}/comments`)
- **Request Wrapper**: Unified request function for all HTTP methods (GET, POST, PUT, DELETE)
- **Authentication**: Per-account token handling (each InstagramAccount has its own `access_token`)
- **Error Handling**: Consistent exception wrapping via HttpClientExceptionDecorator

**Example**: `InstagramBaseClient` provides protected methods (`get()`, `post()`, etc.) that all Instagram API services extend.

### 2. Specific Clients for Specific Endpoints (ALWAYS)

Never create monolithic API clients. Each service class is focused:

- **InstagramApiService**: Handles Instagram Graph API endpoints
  - `getStories(InstagramAccount $account)` → `GET /me/stories`
  - `getStoryComments(InstagramAccount $account, string $storyId)` → `GET /{story_id}/comments`
  - `blockUser(InstagramAccount $account, string $userId)` → `POST /me/blocked`
  - `getUserInfo(InstagramAccount $account, string $username)` → `GET /search`

Each method corresponds to **ONE** specific endpoint and has **ONE** responsibility.

### 3. Multi-Account Architecture (ALWAYS)

**CRITICAL**: This application supports **multiple concurrent Instagram accounts** with **multiple concurrent users**.

#### Key Architectural Decisions

- ❌ **No Global API Keys**: Never use config-based API keys or tokens
- ✅ **Per-Account Tokens**: Each `InstagramAccount` model has its own `access_token` field
- ✅ **Account-Scoped Operations**: ALL API calls require an `InstagramAccount` instance
- ✅ **Concurrent Safety**: 10+ users can operate simultaneously without conflicts

#### User Workflow

```
User A logs in → Manages Account A → Views stories with Account A's token
                 ↓
              Sees unwanted comment → Blocks user with Account A's token

User B logs in → Manages Account B → Views stories with Account B's token
(simultaneously) ↓
              Blocks different user with Account B's token
```

Both operations happen **independently** and **concurrently** without any shared state or conflicts.

### HTTP Client Layer

Three-layer HTTP architecture:

```
[InstagramApiService]
      ↓
[InstagramBaseClient] - Uses account-specific tokens
      ↓
[HttpClientExceptionDecorator] - Wraps exceptions consistently
      ↓
[ExternalClient] - Laravel HTTP client wrapper
      ↓
[Laravel HTTP/Guzzle]
```

- **ExternalClient**: Single request function using Laravel HTTP client
- **HttpClientExceptionDecorator**: Wraps the ExternalClient to handle exceptions consistently
- **InstagramBaseClient**: Abstract base class providing authenticated request methods
- **InstagramApiService**: Concrete implementation for Instagram Graph API endpoints

### Services

- **InstagramApiService** (extends InstagramBaseClient): Handles all Instagram Graph API interactions
  - Get stories from accounts (per-account token)
  - Fetch comments from stories (per-account token)
  - Block users (per-account token)
  - Search for user information (per-account token)

- **BlockedAccountService**: Manages blocked account business logic
  - Create blocked account records (account-scoped)
  - Check if an account is blocked (account-scoped)
  - Retrieve blocked accounts list (account-scoped)

### Models

- **InstagramAccount**: Represents a connected Instagram account
  - Stores `access_token` (guarded for security)
  - Each account operates independently
  - Supports unlimited concurrent accounts

- **BlockedAccount**: Tracks blocked users with reasons and associated comments
  - Belongs to a specific `InstagramAccount`
  - Multiple accounts can block the same username independently

### Filament Resources

- **InstagramAccountResource**: Manage Instagram accounts
  - CRUD operations for accounts
  - Each account has its own access token
  - Custom "View Stories" action (uses account-specific token)
  
- **BlockedAccountResource**: View and manage blocked accounts
  - Filter by Instagram account
  - View block reasons and triggering comments
  - Account-scoped blocking operations

## Instagram Graph API Setup

### Multi-Account Token Management

TrollBeGone supports **unlimited Instagram accounts**, each with its own access token. This means:

- ✅ Multiple users can connect their Instagram accounts
- ✅ Each account operates independently
- ✅ No shared credentials or global API keys
- ✅ Concurrent operations without conflicts

### Prerequisites

To connect an Instagram account, you need:

1. A Facebook Developer account
2. An Instagram Business or Creator account
3. A Facebook App with Instagram Graph API access (Basic Display)
4. A valid access token for each Instagram account you want to monitor

### Setup per Instagram Account

1. Create a Facebook App with Instagram Graph API permissions
2. Generate an access token for the Instagram Business account
3. In TrollBeGone admin panel, create a new Instagram Account
4. Add the username and paste the access token
5. The account is now ready to use independently

**IMPORTANT**: Each `InstagramAccount` model stores its own `access_token`. The application **never** uses a global config-based API key. This architectural decision enables true multi-account, multi-tenant functionality.

## Development

### Code Structure

```
app/
├── Models/                    # Eloquent models
│   ├── InstagramAccount.php  # Multi-account model (each has own token)
│   ├── BlockedAccount.php    # Account-scoped blocked users
│   └── User.php              # Filament admin users
├── Services/
│   ├── Http/                 # HTTP client layer (BaseClient pattern)
│   │   ├── ExternalClient.php
│   │   ├── HttpClientException.php
│   │   └── HttpClientExceptionDecorator.php
│   └── Instagram/            # Instagram API services (specific endpoint clients)
│       ├── InstagramBaseClient.php      # Abstract base with auth methods
│       ├── InstagramApiService.php      # Specific endpoint implementations
│       └── BlockedAccountService.php    # Business logic layer
└── Filament/
    └── Resources/            # Filament admin resources
        ├── InstagramAccounts/    # Multi-account management
        └── BlockedAccounts/      # Account-scoped blocking

database/
└── migrations/               # Database migrations

resources/
└── views/
    └── filament/            # Filament custom views
```

### Key Architectural Files

**Most Important Documentation**:
1. `README.md` (this file) - Project overview and multi-account architecture
2. `.github/copilot-guidelines.md` - Comprehensive development guidelines
3. `.junie/guidelines.md` - Core architectural principles and patterns

**Core Services**:
- `InstagramBaseClient` - BaseClient pattern implementation (API URL, version, endpoints, auth)
- `InstagramApiService` - Specific endpoint clients (one method per endpoint)
- `BlockedAccountService` - Business logic (account-scoped operations)

### Testing

Run all tests:
```bash
php artisan test
```

Run specific test suites:
```bash
# Unit tests (service classes, HTTP clients)
php artisan test --testsuite=Unit

# Feature tests (end-to-end workflows, multi-account scenarios)
php artisan test --testsuite=Feature
```

The test suite includes:
- ✅ Multi-account concurrent operations
- ✅ Per-account token authentication
- ✅ BaseClient pattern validation
- ✅ Specific endpoint client tests
- ✅ Service layer integration tests

## Architectural Principles

TrollBeGone strictly adheres to these principles:

### The Three Pillars

1. **BaseClient Pattern (ALWAYS)**
   - Every external API has a base client
   - Handles: API URL, version, endpoint strings, request wrapper, authentication
   - Example: `InstagramBaseClient` with protected `get()`, `post()`, `put()`, `delete()` methods

2. **Specific Clients for Specific Endpoints (ALWAYS)**
   - One service class per endpoint group
   - One method per endpoint
   - Never create generic "do everything" clients
   - Example: `InstagramApiService::getStories()` calls only `GET /me/stories`

3. **Multi-Account Architecture (ALWAYS)**
   - No global API keys or tokens
   - Each `InstagramAccount` model has its own `access_token`
   - All API calls are account-scoped (require `InstagramAccount` instance)
   - Supports 10+ concurrent users managing different accounts

### SOLID Principles

- **Single Responsibility**: Each class has one job (ExternalClient makes requests, InstagramApiService calls endpoints, BlockedAccountService handles business logic)
- **Open/Closed**: Extend via inheritance (InstagramApiService extends InstagramBaseClient) and decoration (HttpClientExceptionDecorator wraps ExternalClient)
- **Liskov Substitution**: All implementations are substitutable (mock services in tests)
- **Interface Segregation**: Small, focused public interfaces (no fat services)
- **Dependency Inversion**: Inject dependencies via constructor (never instantiate with `new` in business logic)

### Coding Standards

- ✅ Early returns and guard clauses (never nest deeply)
- ✅ Constructor property promotion (PHP 8.0+)
- ✅ Type hints on all parameters and return types
- ✅ Named parameters for clarity
- ✅ PSR-12 code style (enforced by Laravel Pint)
- ✅ Comprehensive PHPDoc on all public methods
- ✅ #region pattern for test organization (Arrange/Act/Assert)

For complete guidelines, see:
- `.github/copilot-guidelines.md` - Full development standards (coding style, testing patterns, Laravel/Filament conventions)
- `.junie/guidelines.md` - Core architectural patterns (BaseClient, multi-account, service layer design)

## License

This project is open-sourced software.
