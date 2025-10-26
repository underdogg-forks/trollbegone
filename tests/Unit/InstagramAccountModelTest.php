<?php

namespace Tests\Unit;

use App\Models\BlockedAccount;
use App\Models\InstagramAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstagramAccountModelTest extends TestCase
{
    use RefreshDatabase;

    public function testInstagramAccountHasFillableAttributes(): void
    {
        $fillable = (new InstagramAccount())->getFillable();

        $this->assertContains('username', $fillable);
        $this->assertContains('instagram_id', $fillable);
        $this->assertContains('access_token', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('last_synced_at', $fillable);
    }

    public function testInstagramAccountCastsAttributesCorrectly(): void
    {
        $account = InstagramAccount::factory()->create([
            'is_active' => 1,
            'last_synced_at' => '2024-01-01 12:00:00',
        ]);

        $this->assertIsBool($account->is_active);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $account->last_synced_at);
    }

    public function testInstagramAccountHasBlockedAccountsRelationship(): void
    {
        $account = InstagramAccount::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $account->blockedAccounts());
    }

    public function testInstagramAccountCanHaveNullInstagramId(): void
    {
        $account = InstagramAccount::factory()->create([
            'instagram_id' => null,
        ]);

        $this->assertNull($account->instagram_id);
        $this->assertDatabaseHas('instagram_accounts', [
            'id' => $account->id,
            'instagram_id' => null,
        ]);
    }

    public function testInstagramAccountCanHaveNullAccessToken(): void
    {
        $account = InstagramAccount::factory()->withoutAccessToken()->create();

        $this->assertNull($account->access_token);
    }

    public function testInstagramAccountDefaultsToActive(): void
    {
        $account = InstagramAccount::factory()->create();

        $this->assertTrue($account->is_active);
    }

    public function testInstagramAccountCanBeInactive(): void
    {
        $account = InstagramAccount::factory()->inactive()->create();

        $this->assertFalse($account->is_active);
    }

    public function testInstagramAccountLastSyncedAtIsNullable(): void
    {
        $account = InstagramAccount::factory()->create([
            'last_synced_at' => null,
        ]);

        $this->assertNull($account->last_synced_at);
    }

    public function testInstagramAccountCanBeUpdated(): void
    {
        $account = InstagramAccount::factory()->create([
            'username' => 'original_username',
        ]);

        $account->update(['username' => 'updated_username']);

        $this->assertEquals('updated_username', $account->fresh()->username);
    }

    public function testInstagramAccountFactoryCreatesUniqueUsernames(): void
    {
        $account1 = InstagramAccount::factory()->create();
        $account2 = InstagramAccount::factory()->create();

        $this->assertNotEquals($account1->username, $account2->username);
    }

    public function testInstagramAccountFactoryCreatesUniqueInstagramIds(): void
    {
        $account1 = InstagramAccount::factory()->create();
        $account2 = InstagramAccount::factory()->create();

        $this->assertNotEquals($account1->instagram_id, $account2->instagram_id);
    }

    public function testInstagramAccountFactoryCreatesUniqueAccessTokens(): void
    {
        $account1 = InstagramAccount::factory()->create();
        $account2 = InstagramAccount::factory()->create();

        $this->assertNotEquals($account1->access_token, $account2->access_token);
    }

    public function testInstagramAccountRelationshipLoadsBlockedAccountsCorrectly(): void
    {
        $account = InstagramAccount::factory()->create();
        
        BlockedAccount::factory()->count(3)->forInstagramAccount($account)->create();

        $this->assertCount(3, $account->blockedAccounts);
    }
}