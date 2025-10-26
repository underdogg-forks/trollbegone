<?php

namespace Tests\Feature;

use App\Models\BlockedAccount;
use App\Models\InstagramAccount;
use App\Services\Http\HttpClientException;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\BlockedAccountService;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlockedAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function blocking_account_creates_database_record(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        $mockHttpClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([
                ['id' => '12345', 'username' => 'spammer'],
            ]);
        $mockHttpClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);
        $mockHttpClient->shouldReceive('post')
            ->once()
            ->andReturn(Mockery::mock(Response::class));
        $instagramApi = new InstagramApiService($mockHttpClient);
        $service = new BlockedAccountService($instagramApi);
        /** #endregion */

        /** #region Act */
        $service->blockAccount($account, 'spammer', 'Spam comments');
        /** #endregion */

        /** #region Assert */
        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $account->id,
            'blocked_username' => 'spammer',
            'blocked_instagram_id' => '12345',
            'reason' => 'Spam comments',
        ]);
        /** #endregion */
    }

    #[Test]
    public function checking_if_user_is_blocked_queries_database(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account->id,
            'blocked_username' => 'blocked_user',
        ]);
        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        /** #endregion */

        /** #region Act */
        $service = new BlockedAccountService($mockInstagramApi);
        /** #endregion */

        /** #region Assert */
        $this->assertTrue($service->isBlocked($account, 'blocked_user'));
        $this->assertFalse($service->isBlocked($account, 'not_blocked_user'));
        /** #endregion */
    }

    #[Test]
    public function get_blocked_accounts_returns_only_account_specific_blocks(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account1 = InstagramAccount::create([
            'username' => 'account1',
            'access_token' => 'token1',
        ]);
        $account2 = InstagramAccount::create([
            'username' => 'account2',
            'access_token' => 'token2',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'user1',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account1->id,
            'blocked_username' => 'user2',
        ]);
        BlockedAccount::create([
            'instagram_account_id' => $account2->id,
            'blocked_username' => 'user3',
        ]);
        $mockInstagramApi = Mockery::mock(InstagramApiService::class);
        $service = new BlockedAccountService($mockInstagramApi);
        $account1Blocks = $service->getBlockedAccounts($account1);
        /** #endregion */

        /** #region Act */
        $account2Blocks = $service->getBlockedAccounts($account2);
        /** #endregion */

        /** #region Assert */
        $this->assertCount(2, $account1Blocks);
        $this->assertCount(1, $account2Blocks);
        /** #endregion */
    }

    #[Test]
    public function blocking_handles_api_failure_gracefully(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        $mockHttpClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([
                ['id' => '12345', 'username' => 'target_user'],
            ]);
        $mockHttpClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);
        $mockHttpClient->shouldReceive('post')
            ->once()
            ->andThrow(new HttpClientException('API Error', 500));
        $instagramApi = new InstagramApiService($mockHttpClient);
        $service = new BlockedAccountService($instagramApi);
        $blockedAccount = $service->blockAccount($account, 'target_user');
        /** #endregion */

        /** #region Act */
        // Record should still be created even if API call fails
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertDatabaseHas('blocked_accounts', [
            'blocked_username' => 'target_user',
        ]);
        /** #endregion */
    }

    #[Test]
    public function blocking_user_not_found_still_creates_record(): void
    {
        $this->markTestIncomplete();

        /** #region Arrange */
        $account = InstagramAccount::create([
            'username' => 'main_account',
            'access_token' => 'test_token',
        ]);
        $mockHttpClient = Mockery::mock(HttpClientExceptionDecorator::class);
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->with('data', [])
            ->andReturn([]);
        $mockHttpClient->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);
        $instagramApi = new InstagramApiService($mockHttpClient);
        $service = new BlockedAccountService($instagramApi);
        /** #endregion */

        /** #region Act */
        $blockedAccount = $service->blockAccount($account, 'ghost_user');
        /** #endregion */

        /** #region Assert */
        $this->assertInstanceOf(BlockedAccount::class, $blockedAccount);
        $this->assertNull($blockedAccount->blocked_instagram_id);
        $this->assertDatabaseHas('blocked_accounts', [
            'blocked_username' => 'ghost_user',
        ]);
        /** #endregion */
    }
}
