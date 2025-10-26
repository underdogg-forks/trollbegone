<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramAccount extends Model
{
    protected $fillable = [
        'username',
        'instagram_id',
        'access_token',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function blockedAccounts(): HasMany
    {
        return $this->hasMany(BlockedAccount::class);
    }
}
