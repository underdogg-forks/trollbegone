<?php

namespace Tests\Unit;

use App\Jobs\BlockUserJob;
use App\Models\Account;
use App\Models\User;
use App\Services\Instagram\BlockedAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tests\Fakes\FakeInstagramApiService;
use Tests\TestCase;

class BlockUserJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_blocks_the_user_when_the_job_is_handled(): void
    {
        /** #region Arrange */
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'token',
        ]);

        $fakeInstagramApi = new FakeInstagramApiService;
        $fakeInstagramApi->setUserInfoResponse('queued_troll', [
            'id' => 'u-200',
            'username' => 'queued_troll',
        ]);
        $fakeInstagramApi->setBlockUserResult('u-200', true);

        $service = new BlockedAccountService($fakeInstagramApi);

        $logger = new class implements LoggerInterface
        {
            /** @var array<int, array{level: string, message: string}> */
            public array $entries = [];

            public function emergency(\Stringable|string $message, array $context = []): void
            {
                $this->entries[] = ['level' => LogLevel::EMERGENCY, 'message' => (string) $message];
            }

            public function alert(\Stringable|string $message, array $context = []): void
            {
                $this->entries[] = ['level' => LogLevel::ALERT, 'message' => (string) $message];
            }

            public function critical(\Stringable|string $message, array $context = []): void
            {
                $this->entries[] = ['level' => LogLevel::CRITICAL, 'message' => (string) $message];
            }

            public function error(\Stringable|string $message, array $context = []): void
            {
                $this->entries[] = ['level' => LogLevel::ERROR, 'message' => (string) $message];
            }

            public function warning(\Stringable|string $message, array $context = []): void
            {
                $this->entries[] = ['level' => LogLevel::WARNING, 'message' => (string) $message];
            }

            public function notice(\Stringable|string $message, array $context = []): void
            {
                $this->entries[] = ['level' => LogLevel::NOTICE, 'message' => (string) $message];
            }

            public function info(\Stringable|string $message, array $context = []): void
            {
                $this->entries[] = ['level' => LogLevel::INFO, 'message' => (string) $message];
            }

            public function debug(\Stringable|string $message, array $context = []): void
            {
                $this->entries[] = ['level' => LogLevel::DEBUG, 'message' => (string) $message];
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->entries[] = ['level' => (string) $level, 'message' => (string) $message];
            }
        };

        $job = new BlockUserJob(
            account: $account,
            username: 'queued_troll',
            reason: 'Blocked from queue job',
            commentText: 'bad comment'
        );
        /** #endregion */

        /** #region Act */
        $job->handle($service, $logger);
        /** #endregion */

        /** #region Assert */
        $this->assertDatabaseHas('blocked_accounts', [
            'instagram_account_id' => $account->id,
            'blocked_username' => 'queued_troll',
            'blocked_instagram_id' => 'u-200',
            'reason' => 'Blocked from queue job',
            'comment_text' => 'bad comment',
        ]);
        $this->assertSame(['u-200'], $fakeInstagramApi->getBlockUserCalls());
        $this->assertNotEmpty($logger->entries);
        $this->assertSame(LogLevel::INFO, $logger->entries[0]['level']);
        /** #endregion */
    }
}
