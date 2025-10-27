<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blocked_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->onDelete('cascade');
            $table->string('blocked_username', 30);
            $table->string('blocked_instagram_id', 64)->nullable();
            $table->text('reason')->nullable();
            $table->text('comment_text')->nullable();
            $table->timestamps();

            $table->unique(['instagram_account_id', 'blocked_instagram_id'], 'unique_account_blocked_id');
            $table->unique(['instagram_account_id', 'blocked_username'], 'unique_account_blocked_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_accounts');
    }
};
