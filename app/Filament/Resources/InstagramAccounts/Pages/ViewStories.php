<?php

namespace App\Filament\Resources\InstagramAccounts\Pages;

use App\Filament\Resources\InstagramAccounts\InstagramAccountResource;
use App\Models\InstagramAccount;
use App\Services\Instagram\InstagramApiService;
use App\Services\Instagram\BlockedAccountService;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ViewStories extends Page
{
    protected static string $resource = InstagramAccountResource::class;

    protected string $view = 'filament.resources.instagram-accounts.pages.view-stories';

    public InstagramAccount $record;

    public array $stories = [];
    public array $comments = [];
    public ?string $selectedStoryId = null;

    public function mount(InstagramAccount $record): void
    {
        $this->record = $record;
        $this->loadStories();
    }

    public function loadStories(): void
    {
        try {
            $instagramApi = app(InstagramApiService::class);
            $this->stories = $instagramApi->getStories($this->record)->toArray();
            
            $this->record->update(['last_synced_at' => now()]);
            
            Notification::make()
                ->title('Stories loaded successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to load stories')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function loadComments(string $storyId): void
    {
        try {
            $this->selectedStoryId = $storyId;
            $instagramApi = app(InstagramApiService::class);
            $this->comments = $instagramApi->getStoryComments($this->record, $storyId)->toArray();
            
            Notification::make()
                ->title('Comments loaded successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to load comments')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function blockUser(string $username, ?string $commentText = null): void
    {
        try {
            $blockedAccountService = app(BlockedAccountService::class);
            $blockedAccountService->blockAccount(
                $this->record,
                $username,
                'Blocked from story comments',
                $commentText
            );
            
            Notification::make()
                ->title("User @{$username} has been blocked")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to block user')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Stories')
                ->action('loadStories'),
        ];
    }
}
