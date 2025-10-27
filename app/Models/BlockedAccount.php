<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blocked Account Model
 *
 * Represents a blocked Instagram user, tracking which Instagram account
 * performed the block, the reason, and the triggering comment (if any).
 *
 * @property int $id
 * @property int $instagram_account_id Foreign key to instagram_accounts table
 * @property string $blocked_username Instagram username of the blocked user
 * @property string|null $blocked_instagram_id Instagram user ID of the blocked user
 * @property string|null $reason Reason for blocking the account
 * @property string|null $comment_text The comment that triggered the block
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\InstagramAccount $instagramAccount
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BlockedAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BlockedAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BlockedAccount query()
 */
class BlockedAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the Instagram account that blocked this user.
     *
     * Relationship: Many BlockedAccounts belong to one InstagramAccount.
     *
     * @return BelongsTo<InstagramAccount, BlockedAccount>
     */
    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }
}
