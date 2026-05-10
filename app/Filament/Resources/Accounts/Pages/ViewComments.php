<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Jobs\BlockUserJob;
use App\Models\Account;
use App\Services\Instagram\InstagramApiService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

/**
 * View Comments Page
 *
 * Displays comments on a specific post with ability to select and block users.
 * Data is fetched directly from Instagram API (no database table).
 */
class ViewComments extends Page
{
    use InteractsWithHeaderActions;

    protected static string $resource = AccountResource::class;

    protected string $view = 'filament.resources.accounts.pages.view-comments';

    public Account $record;

    public string $postId;

    public array $selectedComments = [];

    public array $comments = [];

    public function mount(Account $record, string $post): void
    {
        $this->record = $record;
        $this->postId = $post;
        $this->comments = $this->getCommentsFromApi();
    }

    protected function getCommentsFromApi(): array
    {
        try {
            $apiService = app(InstagramApiService::class);
            $comments = $apiService->getPostComments($this->record, $this->postId);

            return $comments->all();
        } catch (\Exception $e) {
            logger()->error('Failed to fetch comments', [
                'account_id' => $this->record->id,
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function toggleComment(string $commentId): void
    {
        if (in_array($commentId, $this->selectedComments, true)) {
            $this->selectedComments = array_diff($this->selectedComments, [$commentId]);
        } else {
            $this->selectedComments[] = $commentId;
        }
    }

    public function blockSelected(): void
    {
        // Guard clause - ensure comments are selected
        if (empty($this->selectedComments)) {
            Notification::make()
                ->title('No comments selected')
                ->warning()
                ->send();

            return;
        }

        $comments = collect($this->comments)
            ->whereIn('id', $this->selectedComments);

        // Dispatch jobs to block each user
        foreach ($comments as $comment) {
            if (! isset($comment['username'])) {
                continue;
            }

            BlockUserJob::dispatch(
                account: $this->record,
                username: $comment['username'],
                reason: 'Blocked from post comments',
                commentText: $comment['text'] ?? null
            );
        }

        Notification::make()
            ->title('Blocking '.count($comments).' user(s)')
            ->body('Block jobs have been queued')
            ->success()
            ->send();

        // Clear selection
        $this->selectedComments = [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Posts')
                ->icon('heroicon-o-arrow-left')
                ->url(url()->previous()),
            Action::make('block_selected')
                ->label('Block Selected Users')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Block selected users?')
                ->modalDescription('This will block the users who posted the selected comments.')
                ->modalSubmitActionLabel('Block Users')
                ->action(fn () => $this->blockSelected())
                ->disabled(fn () => empty($this->selectedComments)),
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->comments = $this->getCommentsFromApi();
                }),
        ];
    }

    public function getComments(): Collection
    {
        return collect($this->comments);
    }
}
