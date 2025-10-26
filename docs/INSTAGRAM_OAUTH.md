# Instagram OAuth Integration

This document describes how to set up Instagram OAuth authentication for TrollBeGone.

## Overview

TrollBeGone uses Laravel Socialite to authenticate with Instagram's Graph API. This allows users to securely connect their Instagram Business accounts and manage blocked users.

## Prerequisites

1. A Facebook Developer account
2. An Instagram Business account
3. A Facebook App configured for Instagram Basic Display or Instagram Graph API

## Setup Instructions

### 1. Create a Facebook App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Click "My Apps" > "Create App"
3. Select "Business" as the app type
4. Fill in the app details and create the app

### 2. Configure Instagram Graph API

1. In your Facebook App dashboard, go to "Add Products"
2. Add "Instagram Graph API"
3. Configure the following settings:
   - **Valid OAuth Redirect URIs**: `https://yourdomain.com/auth/instagram/callback`
   - **Deauthorize Callback URL**: (optional)
   - **Data Deletion Request URL**: (optional)

### 3. Get API Credentials

1. Go to Settings > Basic in your Facebook App
2. Copy your App ID and App Secret
3. Add these to your `.env` file:

```env
INSTAGRAM_CLIENT_ID=your_app_id
INSTAGRAM_CLIENT_SECRET=your_app_secret
INSTAGRAM_REDIRECT_URI=https://yourdomain.com/auth/instagram/callback
```

### 4. Configure Permissions

Your app needs the following permissions:
- `instagram_basic` - Read basic account info
- `instagram_manage_comments` - Read and manage comments
- `instagram_manage_insights` - Access to insights data

### 5. Test the Integration

1. Log in to your Filament admin panel
2. Navigate to Instagram Accounts
3. Click "Connect Instagram Account"
4. Authorize your Instagram Business account
5. The account will be saved with the access token

## Usage

### Connecting an Account

Users can connect their Instagram accounts through the Filament admin panel:

```php
// Navigate to: /admin/instagram-accounts
// Click: "Connect Instagram Account"
```

### Disconnecting an Account

To disconnect an account and revoke access:

```php
// In the Instagram Accounts list, click the disconnect action
```

### API Endpoints

The following endpoints are available:

- `GET /auth/instagram/redirect` - Initiates OAuth flow
- `GET /auth/instagram/callback` - Handles OAuth callback
- `POST /auth/instagram/disconnect/{account}` - Disconnects an account

## Security Considerations

1. **Environment Variables**: Store credentials in `.env` file, never commit them
2. **HTTPS**: Always use HTTPS in production for OAuth callbacks
3. **Token Storage**: Access tokens are encrypted in the database
4. **Token Refresh**: Implement token refresh logic for long-lived tokens

## Troubleshooting

### Common Issues

**Error: "Redirect URI mismatch"**
- Ensure the redirect URI in Facebook App settings matches your `.env` file
- Check for trailing slashes or protocol mismatches (http vs https)

**Error: "Invalid OAuth access token"**
- The token may have expired
- Re-authenticate the account through the admin panel

**Error: "Insufficient permissions"**
- Check that your app has requested the necessary permissions
- Re-authenticate to grant new permissions

## Testing

Mock the Socialite facade in tests:

```php
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;

Socialite::shouldReceive('driver->user')
    ->andReturn((new User)->map([
        'id' => '123456',
        'nickname' => 'test_user',
    ]));
```

## Further Reading

- [Instagram Graph API Documentation](https://developers.facebook.com/docs/instagram-api)
- [Laravel Socialite Documentation](https://laravel.com/docs/socialite)
- [Facebook Login Documentation](https://developers.facebook.com/docs/facebook-login)
