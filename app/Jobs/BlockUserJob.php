<?php

namespace App\Jobs;

use App\Models\Account;
use App\Services\Instagram\BlockedAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Job to block a user on Instagram and record it in the database.
 *
 * This job is dispatched when Grandma selects comments to block users.
 * It calls the BlockedAccountService which handles the API interaction
 * and database persistence.
 */
class BlockUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  Account  $account  The account performing the block
     * @param  string  $username  The username to block
     * @param  string|null  $reason  Optional reason for blocking
     * @param  string|null  $commentText  Optional comment that triggered the block
     */
    public function __construct(
        public Account $account,
        public string $username,
        public ?string $reason = null,
        public ?string $commentText = null
    ) {}

    /**
     * Execute the job.
     *
     * Delegates to BlockedAccountService to perform the block operation.
     * Laravel's queue handles failures, logging, and retries automatically.
     */
    public function handle(BlockedAccountService $service, LoggerInterface $logger): void
    {
        $service->blockAccount(
            account: $this->account,
            username: $this->username,
            reason: $this->reason,
            commentText: $this->commentText
        );

        $logger->info('User blocked successfully', [
            'account_id' => $this->account->id,
            'username' => $this->username,
        ]);
    }
}
