<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BlockedAccount extends Model
{
    protected $fillable = [
        'instagram_account_id',
        'blocked_username',
        'blocked_instagram_id',
        'reason',
        'comment_text',
    ];

    public function setBlockedUsernameAttribute(?string $value): void
    {
        $this->attributes['blocked_username'] = $value !== null ? Str::lower($value) : null;
    }

    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(InstagramAccount::class);
    }
}
