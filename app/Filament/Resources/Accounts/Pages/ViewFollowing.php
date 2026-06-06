<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Models\Account;
use App\Services\Instagram\InstagramApiService;
use Filament\Actions\Action;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * View Following Page
 *
 * Displays users that Grandma follows on Instagram.
 * Data is fetched directly from Instagram API (no database table).
 */
class ViewFollowing extends Page implements HasTable
{
    use InteractsWithHeaderActions;
    use InteractsWithTable;

    protected static string $resource = AccountResource::class;

    protected string $view = 'filament.resources.accounts.pages.view-following';

    public Account $record;

    public array $following = [];

    public function mount(Account $record): void
    {
        $this->record = $record;
        $this->following = $this->getFollowingFromApi();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getFollowing())
            ->columns([
                ImageColumn::make('profile_picture_url')
                    ->label('Avatar')
                    ->circular(),
                TextColumn::make('username')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record['full_name'] ?? ''),
                TextColumn::make('followers_count')
                    ->label('Followers')
                    ->numeric()
                    ->sortable()
                    ->default(0),
            ])
            ->recordActions([
                Action::make('view_posts')
                    ->label('View Posts')
                    ->icon('heroicon-o-photo')
                    ->url(fn ($record) => route('filament.admin.resources.accounts.posts', [
                        'record' => $this->record->id,
                        'username' => $record['username'],
                    ])),
            ]);
    }

    protected function getFollowingFromApi(): array
    {
        try {
            return app(InstagramApiService::class)
                ->getFollowing($this->record)
                ->all();
        } catch (\Exception $e) {
            \Log::error('Failed to fetch following list', [
                'account_id' => $this->record->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->following = $this->getFollowingFromApi();
                }),
        ];
    }

    public function getFollowing(): Collection
    {
        return collect($this->following);
    }
}
