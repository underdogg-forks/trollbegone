# TrollBeGone

A Laravel 12 application with Filament v4 for managing Instagram story comments and blocking unwanted accounts.

## Features

- **Instagram Account Management**: Track multiple Instagram accounts
- **Story Monitoring**: View stories from connected Instagram accounts
- **Comment Moderation**: Review comments on stories
- **Account Blocking**: Block users directly from comment review
- **Blocked Account Tracking**: Maintain a list of blocked accounts with reasons

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

### HTTP Client Layer

The application uses a custom HTTP client architecture:

- **ExternalClient**: Single request function using Laravel HTTP client (similar to Guzzle)
- **HttpClientExceptionDecorator**: Wraps the ExternalClient to handle exceptions consistently

### Services

- **InstagramApiService**: Handles all Instagram Graph API interactions
  - Get stories from accounts
  - Fetch comments from stories
  - Block users
  - Search for user information

- **BlockedAccountService**: Manages blocked account logic
  - Create blocked account records
  - Check if an account is blocked
  - Retrieve blocked accounts list

### Models

- **InstagramAccount**: Represents a connected Instagram account
- **BlockedAccount**: Tracks blocked users with reasons and associated comments

### Filament Resources

- **InstagramAccountResource**: Manage Instagram accounts
  - CRUD operations for accounts
  - Custom "View Stories" action
  
- **BlockedAccountResource**: View and manage blocked accounts
  - Filter by Instagram account
  - View block reasons and triggering comments

## Instagram Graph API Setup

To use this application, you need:

1. A Facebook Developer account
2. An Instagram Business or Creator account
3. A Facebook App with Instagram Basic Display API access
4. Valid access tokens for each Instagram account you want to monitor

Add the access token when creating an Instagram Account in the admin panel.

## Development

### Code Structure

```
app/
├── Models/               # Eloquent models
├── Services/
│   ├── Http/            # HTTP client layer
│   └── Instagram/       # Instagram API services
└── Filament/
    └── Resources/       # Filament admin resources

database/
└── migrations/          # Database migrations

resources/
└── views/
    └── filament/        # Filament custom views
```

### Testing

Run tests with:
```bash
php artisan test
```

## License

This project is open-sourced software.
