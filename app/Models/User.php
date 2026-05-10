<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Application User Model
 *
 * Represents an authenticated user of the TrollBeGone application. Extends
 * Laravel's authenticatable model and leverages factories and notifications
 * for testing and user messaging.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Account> $instagramAccounts
 * @property-read int|null $instagram_accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Account> $accounts
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get all Instagram accounts owned by this user.
     *
     * Relationship: One User has many Accounts.
     *
     * @return HasMany<\App\Models\Account>
     */
    public function instagramAccounts(): HasMany
    {
        return $this->hasMany(\App\Models\Account::class);
    }

    /**
     * Alias for instagramAccounts() for convenience.
     *
     * Relationship: One User has many Accounts.
     *
     * @return HasMany<\App\Models\Account>
     */
    public function accounts(): HasMany
    {
        return $this->instagramAccounts();
    }
}
