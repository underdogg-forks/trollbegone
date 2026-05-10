# TrollBeGone

## 🔒 Privacy & Permissions

**Important:** TrollBeGone only accesses what you explicitly allow. See [Privacy and Permissions](docs/PRIVACY_AND_PERMISSIONS.md) for details on:
- What data we can and cannot access
- OAuth scopes explained
- GDPR compliance
- How to revoke access

**TL;DR:** We can view your following list, read comments, and block users. We **cannot** post, DM, or access private data.

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
- Asks for required permissions (`instagram_basic`, `instagram_manage_comments`, `instagram_manage_insights`).
- Receives an access token after approval.
- Saves that token in `instagram_accounts.access_token` for the connected account.
- Reconnecting the same account updates/replaces the token automatically.

**What we request access to:**
- ✅ View your following list (read-only)
- ✅ Read comments on posts (read-only)
- ✅ Block users (same as manual blocking)
- ✅ Delete comments (same as manual deletion)
- ❌ Cannot post, like, comment, or DM
- ❌ Cannot access private information

See [docs/PRIVACY_AND_PERMISSIONS.md](docs/PRIVACY_AND_PERMISSIONS.md) for complete details.
