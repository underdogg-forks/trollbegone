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
use Livewire\Attributes\Locked;

/**
 * List Comments Page
 *
 * Displays post comments and provides single/bulk moderation actions.
 */
class ListComments extends Page
{
    use InteractsWithHeaderActions;

    protected static string $resource = AccountResource::class;

    protected string $view = 'filament.resources.accounts.pages.list-comments';

    public Account $record;

    public string $postId;

    public array $selectedComments = [];

    #[Locked]
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
            $this->selectedComments = $this->removeSelectedItems(
                selectedItems: $this->selectedComments,
                itemsToRemove: [$commentId]
            );

            return;
        }

        $this->selectedComments[] = $commentId;
    }

    public function blockSelected(): void
    {
        if (empty($this->selectedComments)) {
            Notification::make()
                ->title('No comments selected')
                ->warning()
                ->send();

            return;
        }

        $comments = collect($this->comments)
            ->whereIn('id', $this->selectedComments);

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

        $this->selectedComments = [];
    }

    public function deleteAndBlockComment(string $commentId): void
    {
        $comment = collect($this->comments)
            ->first(fn (array $item): bool => ($item['id'] ?? null) === $commentId);

        if (! $comment || ! isset($comment['username'])) {
            Notification::make()
                ->title('Comment user not found')
                ->warning()
                ->send();

            return;
        }

        $apiService = app(InstagramApiService::class);
        $deleted = $apiService->deleteComment($this->record, $commentId);

        if (! $deleted) {
            Notification::make()
                ->title('Delete failed')
                ->warning()
                ->send();

            return;
        }

        BlockUserJob::dispatch(
            account: $this->record,
            username: $comment['username'],
            reason: 'Deleted and blocked from post comments',
            commentText: $comment['text'] ?? null
        );

        $this->comments = collect($this->comments)
            ->reject(fn (array $item): bool => ($item['id'] ?? null) === $commentId)
            ->values()
            ->all();

        $this->selectedComments = $this->removeSelectedItems(
            selectedItems: $this->selectedComments,
            itemsToRemove: [$commentId]
        );

        Notification::make()
            ->title('Comment deleted and user queued for block')
            ->success()
            ->send();
    }

    public function bulkDeleteTaggedComments(string $tag = 'trollbegone'): void
    {
        $apiService = app(InstagramApiService::class);
        $taggedComments = $apiService->filterCommentsByTag(collect($this->comments), $tag);

        if ($taggedComments->isEmpty()) {
            Notification::make()
                ->title('No tagged comments found')
                ->warning()
                ->send();

            return;
        }

        $deletedCount = 0;

        foreach ($taggedComments as $comment) {
            if (! isset($comment['id'], $comment['username'])) {
                continue;
            }

            if (! $apiService->deleteComment($this->record, $comment['id'])) {
                continue;
            }

            $deletedCount++;

            BlockUserJob::dispatch(
                account: $this->record,
                username: $comment['username'],
                reason: 'Bulk deleted and blocked from tagged comments',
                commentText: $comment['text'] ?? null
            );
        }

        $taggedCommentIds = $taggedComments->pluck('id')->all();
        $this->comments = collect($this->comments)
            ->reject(fn (array $item): bool => in_array($item['id'] ?? null, $taggedCommentIds, true))
            ->values()
            ->all();
        $this->selectedComments = $this->removeSelectedItems(
            selectedItems: $this->selectedComments,
            itemsToRemove: $taggedCommentIds
        );

        Notification::make()
            ->title("Deleted {$deletedCount} tagged comments and queued blocks")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Posts')
                ->icon('heroicon-o-arrow-left')
                ->url(url()->previous()),
            Action::make('view_commenters')
                ->label('List Commenters')
                ->icon('heroicon-o-users')
                ->url(AccountResource::getUrl('commenters', [
                    'record' => $this->record,
                    'post' => $this->postId,
                ])),
            Action::make('bulk_delete_trolls')
                ->label('Delete #TrollBeGone')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete tagged comments and block users?')
                ->modalDescription('This removes #trollbegone comments and queues block jobs for their authors.')
                ->modalSubmitActionLabel('Delete + Block')
                ->action(fn () => $this->bulkDeleteTaggedComments()),
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

    /**
     * Remove IDs from a selected items list and return reindexed values.
     *
     * @param  array<int, string>  $selectedItems
     * @param  array<int, string>  $itemsToRemove
     * @return array<int, string>
     */
    private function removeSelectedItems(array $selectedItems, array $itemsToRemove): array
    {
        return array_values(array_diff($selectedItems, $itemsToRemove));
    }
}
