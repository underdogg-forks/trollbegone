<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Account Model
 *
 * Represents an Instagram Business account connected to the application.
 * Each account can have multiple blocked accounts and maintains an access
 * token for Instagram Graph API authentication.
 *
 * @property int $id
 * @property int $user_id User who owns this account
 * @property string $username Instagram username
 * @property string|null $instagram_id Instagram user ID from the API
 * @property string|null $access_token Instagram Graph API access token
 * @property bool $is_active Whether the account is currently active
 * @property \Carbon\Carbon|null $last_synced_at Last time data was fetched
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BlockedAccount> $blockedAccounts
 * @property-read int|null $blocked_accounts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account query()
 */
class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'instagram_id',
        'access_token',
        'is_active',
        'last_synced_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'access_token' => 'encrypted',
    ];

    /**
     * Specify the table name for this model.
     */
    protected $table = 'instagram_accounts';

    /**
     * Get the user who owns this account.
     *
     * Relationship: One Account belongs to one User.
     *
     * @return BelongsTo<User, Account>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all blocked accounts for this account.
     *
     * Relationship: One Account has many BlockedAccounts.
     *
     * @return HasMany<BlockedAccount>
     */
    public function blockedAccounts(): HasMany
    {
        return $this->hasMany(BlockedAccount::class, 'instagram_account_id');
    }
}
