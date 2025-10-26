<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            DB::statement('UPDATE blocked_accounts SET blocked_username = LOWER(blocked_username) WHERE blocked_username IS NOT NULL');

            $duplicates = DB::table('blocked_accounts')
                ->select('instagram_account_id', DB::raw('LOWER(blocked_username) as normalized_username'), DB::raw('MIN(id) as keep_id'))
                ->groupBy('instagram_account_id', DB::raw('LOWER(blocked_username)'))
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicates as $duplicate) {
                DB::table('blocked_accounts')
                    ->where('instagram_account_id', $duplicate->instagram_account_id)
                    ->whereRaw('LOWER(blocked_username) = ?', [$duplicate->normalized_username])
                    ->where('id', '<>', $duplicate->keep_id)
                    ->delete();
            }
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX blocked_accounts_account_username_unique ON blocked_accounts (instagram_account_id, LOWER(blocked_username))');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE blocked_accounts ADD UNIQUE INDEX blocked_accounts_account_username_unique (instagram_account_id, blocked_username)');
        } else {
            Schema::table('blocked_accounts', function (Blueprint $table) {
                $table->unique(['instagram_account_id', 'blocked_username'], 'blocked_accounts_account_username_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS blocked_accounts_account_username_unique');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE blocked_accounts DROP INDEX blocked_accounts_account_username_unique');
        } else {
            Schema::table('blocked_accounts', function (Blueprint $table) {
                $table->dropUnique('blocked_accounts_account_username_unique');
            });
        }
    }
};
