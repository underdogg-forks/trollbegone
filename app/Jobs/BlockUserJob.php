<?php

namespace App\Jobs;

use App\Models\Account;
use App\Services\Instagram\BlockedAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job to block a user on Instagram and record it in the database.
 *
 * This job is dispatched when Grandma selects comments to block users.
 * It calls the BlockedAccountService which handles the API interaction
 * and database persistence.
 */
class BlockUserJob implements ShouldQueue
{
    use Queueable;

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
     * Logs any errors that occur during execution.
     */
    public function handle(BlockedAccountService $service): void
    {
        try {
            $service->blockAccount(
                account: $this->account,
                username: $this->username,
                reason: $this->reason,
                commentText: $this->commentText
            );

            Log::info('User blocked successfully', [
                'account_id' => $this->account->id,
                'username' => $this->username,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to block user in job', [
                'account_id' => $this->account->id,
                'username' => $this->username,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }
}
