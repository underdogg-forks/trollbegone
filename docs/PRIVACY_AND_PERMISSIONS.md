# Privacy, Permissions, and Data Usage

## Overview

TrollBeGone is designed with privacy and data minimization in mind. This document explains what data we access, what we can and cannot do with your Instagram account, and how we handle your information.

## OAuth Scopes and Permissions

When you connect your Instagram account to TrollBeGone, you grant the following permissions:

### Requested Scopes

```php
// From InstagramOAuthController.php
->scopes([
    'instagram_basic',
    'instagram_manage_comments',
    'instagram_manage_insights'
])
```

### What Each Scope Allows

#### 1. `instagram_basic` (Read-Only)
**What we CAN do:**
- Read your Instagram username and user ID
- View your profile information
- See the list of users you follow

**What we CANNOT do:**
- Post on your behalf
- Like or comment on posts
- Follow or unfollow users
- Access your direct messages
- View your private information (email, phone number)

#### 2. `instagram_manage_comments` (Read and Moderate)
**What we CAN do:**
- Read comments on your posts and stories
- Delete comments on your posts and stories
- Hide/unhide comments

**What we CANNOT do:**
- Post comments on your behalf
- Reply to comments automatically
- Modify existing comments
- Access comments on other users' posts

#### 3. `instagram_manage_insights` (Read-Only Analytics)
**What we CAN do:**
- View basic engagement metrics (views, reach)
- Access story insights

**What we CANNOT do:**
- Modify any analytics data
- Share your analytics with third parties
- Use analytics for purposes other than displaying them to you

### What TrollBeGone Actually Uses

Currently, TrollBeGone only uses a subset of these permissions:

| Feature | Scope Used | API Endpoint | Purpose |
|---------|-----------|--------------|---------|
| View Following | `instagram_basic` | `GET /me/following` | Display users you follow |
| View Posts | `instagram_basic` | `GET /{user_id}/media` | Show posts from followed users |
| View Comments | `instagram_manage_comments` | `GET /{post_id}/comments` | Display comments for moderation |
| Block Users | `instagram_basic` | `POST /me/blocked` | Block unwanted accounts |
| Delete Comments | `instagram_manage_comments` | `DELETE /{comment_id}` | Remove spam/troll comments |

**Note:** The `instagram_manage_insights` scope is requested for future features but is not currently used.

## What We Can and Cannot Do

### ✅ What TrollBeGone CAN Do

1. **View Your Following List**
   - See which accounts you follow
   - Display their usernames and profile pictures
   - Navigate to their posts

2. **View Posts and Comments**
   - Read posts from users you follow
   - Read comments on those posts
   - Display comment text and usernames

3. **Block Users**
   - Add users to your Instagram blocked list
   - This is the same as manually blocking them in Instagram

4. **Delete Comments**
   - Remove comments from your posts/stories
   - This is the same as manually deleting them in Instagram

5. **Store Minimal Data**
   - Your Instagram username and user ID
   - Your access token (encrypted)
   - List of users you've blocked through TrollBeGone
   - Reasons for blocking (for your audit trail)

### ❌ What TrollBeGone CANNOT Do

1. **Cannot Post or Interact**
   - Cannot create posts, stories, or reels
   - Cannot like, comment, or share content
   - Cannot follow or unfollow users
   - Cannot send direct messages

2. **Cannot Access Private Data**
   - Cannot see your email address
   - Cannot see your phone number
   - Cannot access your direct messages
   - Cannot view your saved posts or collections

3. **Cannot Act Without Your Explicit Action**
   - Cannot automatically block users
   - Cannot delete comments without your selection
   - Cannot perform any action in the background without your click

4. **Cannot Share Your Data**
   - Does not sell or share your data with third parties
   - Does not use your data for advertising
   - Does not train AI models on your data

## Data Storage and Retention

### What We Store

```sql
-- instagram_accounts table
- username (your Instagram username)
- instagram_id (your Instagram user ID)
- access_token (encrypted OAuth token)
- is_active (whether account is connected)
- last_synced_at (last time we fetched data)

-- blocked_accounts table
- blocked_username (username you blocked)
- blocked_instagram_id (their Instagram ID)
- reason (why you blocked them - optional)
- comment_text (the comment that triggered the block - optional)
```

### What We Do NOT Store

- ❌ Your posts or stories
- ❌ Comments from other users (except temporarily for display)
- ❌ Your followers or following list (fetched on-demand only)
- ❌ Your email, phone, or personal information
- ❌ Your direct messages
- ❌ Your analytics or insights data

### Data Encryption

- **Access Tokens**: Encrypted at rest using Laravel's encryption
- **Database**: Can be encrypted at the database level (your choice)
- **Transmission**: All API calls use HTTPS

### Data Retention

- **Active Accounts**: Data retained while account is connected
- **Disconnected Accounts**: Access token is immediately deleted
- **Blocked Accounts List**: Retained for audit trail (you can delete manually)
- **Logs**: Server logs rotated according to your configuration

## GDPR Compliance

TrollBeGone is designed to be GDPR-compliant:

### Your Rights

1. **Right to Access**
   - View all data we store about you in the admin panel
   - Export your blocked accounts list

2. **Right to Rectification**
   - Update your account information
   - Correct any inaccurate data

3. **Right to Erasure ("Right to be Forgotten")**
   - Disconnect your account to delete your access token
   - Delete your blocked accounts list
   - Request complete account deletion

4. **Right to Data Portability**
   - Export your blocked accounts list as CSV/JSON
   - Take your data to another service

5. **Right to Restrict Processing**
   - Disconnect your account to stop all API calls
   - Deactivate your account without deleting data

6. **Right to Object**
   - Opt out of any data processing
   - Disconnect at any time

### How to Exercise Your Rights

```php
// Disconnect Account (deletes access token)
// Admin Panel → Instagram Accounts → Disconnect

// Delete Blocked Accounts List
// Admin Panel → Blocked Accounts → Select All → Delete

// Export Data
// Admin Panel → Blocked Accounts → Export

// Complete Account Deletion
// Contact your administrator or delete via admin panel
```

### Data Processing Basis

- **Consent**: You explicitly authorize TrollBeGone when connecting your account
- **Legitimate Interest**: Protecting your Instagram account from spam/trolls
- **Contract**: Providing the service you requested

### Data Controller

- **You** are the data controller for your Instagram account
- **TrollBeGone** is the data processor acting on your instructions
- **Instagram/Meta** is the data controller for Instagram data

## Security Measures

### Access Control

- ✅ Each user can only access their own Instagram accounts
- ✅ Authorization checks on every action
- ✅ Laravel's built-in authentication and authorization
- ✅ Filament's role-based access control

### Token Security

- ✅ Tokens encrypted at rest
- ✅ Tokens never logged or displayed
- ✅ Tokens transmitted over HTTPS only
- ✅ Tokens can be revoked at any time

### Audit Trail

- ✅ All blocking actions logged with timestamp
- ✅ Reason for blocking stored (optional)
- ✅ Comment text that triggered block stored (optional)
- ✅ No automatic actions without user confirmation

## Revoking Access

### How to Disconnect

1. **In TrollBeGone:**
   - Go to Admin → Instagram Accounts
   - Click "Disconnect" on your account
   - Access token is immediately deleted

2. **In Instagram/Facebook:**
   - Go to Instagram Settings → Security → Apps and Websites
   - Find TrollBeGone and click "Remove"
   - Or go to Facebook Settings → Business Integrations
   - Find your app and revoke access

### What Happens When You Disconnect

- ✅ Access token is deleted from database
- ✅ TrollBeGone can no longer access your Instagram account
- ✅ Your blocked accounts list remains (for audit trail)
- ✅ You can reconnect at any time with a new token

## Frequently Asked Questions

### Can TrollBeGone post on my Instagram?

**No.** TrollBeGone does not request posting permissions and cannot create posts, stories, or comments on your behalf.

### Can TrollBeGone see my direct messages?

**No.** TrollBeGone does not request DM permissions and cannot access your direct messages.

### Can TrollBeGone follow/unfollow users automatically?

**No.** TrollBeGone does not request follow permissions and cannot follow or unfollow users.

### What happens if TrollBeGone is hacked?

- Access tokens are encrypted at rest
- Tokens are useless without the encryption key
- You can revoke access at any time through Instagram
- We recommend regular token rotation (reconnect periodically)

### Can I use TrollBeGone with a personal Instagram account?

**No.** Instagram Graph API requires a Business or Creator account. Personal accounts are not supported by Instagram's API.

### Does TrollBeGone work while I'm offline?

**No.** TrollBeGone fetches data on-demand from Instagram's API. When you view following/posts/comments, we make real-time API calls. Nothing is cached or stored permanently.

### Can multiple people manage the same Instagram account?

**Yes.** Multiple TrollBeGone users can connect the same Instagram account. Each will have their own access token and can manage blocks independently.

### What if I want to delete all my data?

1. Disconnect your Instagram account (deletes access token)
2. Delete your blocked accounts list
3. Delete your TrollBeGone user account
4. All your data is now removed

## Contact and Data Protection Officer

For privacy concerns, data requests, or questions:

- **GitHub Issues**: [Report privacy concerns](https://github.com/underdogg-forks/trollbegone/issues)
- **Email**: Contact your TrollBeGone administrator
- **Data Protection Officer**: Contact your organization's DPO

## Changes to This Policy

This privacy policy may be updated as TrollBeGone evolves. Check the git history for changes:

```bash
git log docs/PRIVACY_AND_PERMISSIONS.md
```

## Summary

**TL;DR:**
- ✅ We only access what you explicitly allow
- ✅ We only store minimal data (username, token, blocked list)
- ✅ We never post, comment, or act without your click
- ✅ You can disconnect and delete your data anytime
- ✅ Your data is encrypted and never shared
- ✅ GDPR-compliant with full data portability

**You are in control.** TrollBeGone is a tool that acts on your instructions, not an autonomous agent.
