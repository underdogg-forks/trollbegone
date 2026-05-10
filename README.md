# TrollBeGone

### Connect Instagram (Non-Technical Walkthrough)

You do **not** paste API tokens manually.

If your admin already configured the app credentials, you only need to:

1. Log in to TrollBeGone.
2. Go to **Admin → Instagram Accounts**.
3. Click **Connect Instagram**.
4. Sign in to Instagram/Facebook and approve access.
5. You return to TrollBeGone and your account shows as connected.

That's it. Socialite handles the OAuth2 redirect flow and TrollBeGone stores the returned access token for that specific Instagram account.

### What Socialite Is Doing Behind the Scenes

- Opens Instagram/Facebook login page securely.
- Asks for required permissions.
- Receives an access token after approval.
- Saves that token in `instagram_accounts.access_token` for the connected account.
- Reconnecting the same account updates/replaces the token automatically.
