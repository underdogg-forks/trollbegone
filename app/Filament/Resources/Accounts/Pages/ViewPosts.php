<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Enums\RequestMethod;
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

    protected $posts = [];

    public function mount(int|string $record, string $username): void
    {
        $this->record = Account::findOrFail($record);
        $this->username = $username;
        $this->posts = $this->getPostsFromApi();
    }

    protected function getPostsFromApi(): array
    {
        try {
            $apiService = app(InstagramApiService::class);

            // First, get the user ID
            $userInfo = $apiService->getUserInfo($this->record, $this->username);

            if (! $userInfo || ! isset($userInfo['id'])) {
                return [];
            }

            // Fetch posts for this user
            $response = $apiService->request(
                RequestMethod::GET,
                $this->record,
                "/{$userInfo['id']}/media",
                ['query' => ['fields' => 'id,caption,media_type,media_url,permalink,timestamp,like_count,comments_count']]
            );

            return $response->json('data', []);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch posts', [
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
                ->url(route('filament.admin.resources.accounts.following', ['record' => $this->record->id])),
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
}
