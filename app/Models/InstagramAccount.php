<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Instagram Account Model
 *
 * Represents an Instagram Business account connected to the application.
 * Each account can have multiple blocked accounts and maintains an access
 * token for Instagram Graph API authentication.
 *
 * @property int $id
 * @property string $username Instagram username
 * @property string|null $instagram_id Instagram user ID from the API
 * @property string|null $access_token Instagram Graph API access token
 * @property bool $is_active Whether the account is currently active
 * @property \Carbon\Carbon|null $last_synced_at Last time stories were fetched
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BlockedAccount> $blockedAccounts
 * @property-read int|null $blocked_accounts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|InstagramAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InstagramAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InstagramAccount query()
 */
class InstagramAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'instagram_id',
        'is_active',
        'last_synced_at',
    ];

    protected $guarded = [
        'access_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get all blocked accounts for this Instagram account.
     *
     * Relationship: One InstagramAccount has many BlockedAccounts.
     *
     * @return HasMany<BlockedAccount>
     */
    public function blockedAccounts(): HasMany
    {
        return $this->hasMany(BlockedAccount::class);
    }
}
