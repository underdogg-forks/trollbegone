<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\BlockedAccount;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedE2EData extends Command
{
    protected $signature = 'e2e:seed';

    protected $description = 'Seed deterministic data for Playwright end-to-end tests';

    public function handle(): int
    {
        DB::transaction(function () {
            $user = User::create([
                'name' => 'E2E Admin',
                'email' => 'e2e@example.com',
                'password' => bcrypt('password'),
            ]);

            $account = Account::create([
                'user_id' => $user->id,
                'username' => 'primary_account',
                'instagram_id' => 'ig-primary',
                'access_token' => 'token1',
                'is_active' => true,
            ]);

            $secondAccount = Account::create([
                'user_id' => $user->id,
                'username' => 'secondary_account',
                'instagram_id' => 'ig-secondary',
                'access_token' => 'token2',
                'is_active' => true,
            ]);

            BlockedAccount::create([
                'instagram_account_id' => $account->id,
                'blocked_username' => 'seeded_troll',
                'blocked_instagram_id' => '999',
                'reason' => 'Seed reason',
            ]);

            $this->info(json_encode([
                'email' => $user->email,
                'password' => 'password',
                'account_id' => $account->id,
                'account_id_2' => $secondAccount->id,
            ]));
        });

        return self::SUCCESS;
    }
}
