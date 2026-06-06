<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Models\Account;
use App\Services\Instagram\InstagramApiService;
use Filament\Actions\Action;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

/**
 * View Posts Page
 *
 * Displays posts from a specific user that Grandma follows.
 * Data is fetched directly from Instagram API (no database table).
 */
class ViewPosts extends Page
{
    use InteractsWithHeaderActions;

    protected static string $resource = AccountResource::class;

    protected string $view = 'filament.resources.accounts.pages.view-posts';

    public Account $record;

    public string $username;

    public array $posts = [];

    public function mount(Account $record, string $username): void
    {
        $this->record = $record;
        $this->username = $username;
        $this->posts = $this->getPostsFromApi();
    }

    protected function getPostsFromApi(): array
    {
        try {
            $apiService = app(InstagramApiService::class);
            $posts = $apiService->getPostsByUsername($this->record, $this->username);

            return $posts->all();
        } catch (\Exception $e) {
            logger()->error('Failed to fetch posts', [
                'account_id' => $this->record->id,
                'username' => $this->username,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Following')
                ->icon('heroicon-o-arrow-left')
                ->url(AccountResource::getUrl('following', ['record' => $this->record])),
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->posts = $this->getPostsFromApi();
                }),
        ];
    }

    public function getPosts(): Collection
    {
        return collect($this->posts);
    }

    /**
     * Format a timestamp for display.
     */
    public function formatTimestamp(string $timestamp): string
    {
        return \Carbon\Carbon::parse($timestamp)->diffForHumans();
    }
}
