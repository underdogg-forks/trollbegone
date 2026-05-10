<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Models\Account;
use App\Services\Instagram\CommentModerationService;
use App\Services\Instagram\InstagramApiService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;

/**
 * List Commenters Page
 *
 * Displays unique commenters for a post and allows bulk blocking.
 * This is a custom resource page backed by Instagram API data (not Eloquent records).
 */
class ListCommenters extends Page
{
    use InteractsWithHeaderActions;

    protected static string $resource = AccountResource::class;

    protected string $view = 'filament.resources.accounts.pages.list-commenters';

    public Account $record;

    public string $postId;

    public array $selectedCommenters = [];

    protected InstagramApiService $instagramApi;

    protected CommentModerationService $commentModerationService;

    #[Locked]
    public array $comments = [];

    /**
     * Inject dependencies via Livewire's boot method.
     */
    public function boot(
        InstagramApiService $instagramApi,
        CommentModerationService $commentModerationService
    ): void {
        $this->instagramApi = $instagramApi;
        $this->commentModerationService = $commentModerationService;
    }

    public function mount(Account $record, string $post): void
    {
        $this->record = $record;
        $this->postId = $post;
        $this->comments = $this->getCommentsFromApi();
    }

    protected function getCommentsFromApi(): array
    {
        try {
            $comments = $this->instagramApi->getPostComments($this->record, $this->postId);

            return $comments->all();
        } catch (\Exception $e) {
            logger()->error('Failed to fetch commenters', [
                'account_id' => $this->record->id,
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function toggleCommenter(string $username): void
    {
        if (in_array($username, $this->selectedCommenters, true)) {
            $this->selectedCommenters = array_values(array_diff($this->selectedCommenters, [$username]));

            return;
        }

        $this->selectedCommenters[] = $username;
    }

    public function blockSelectedCommenters(): void
    {
        if (empty($this->selectedCommenters)) {
            Notification::make()
                ->title('No commenters selected')
                ->warning()
                ->send();

            return;
        }

        $commentsByUser = $this->commentModerationService->getCommentsGroupedBySelectedUsernames(
            comments: collect($this->comments),
            selectedCommenters: $this->selectedCommenters
        );
        $this->commentModerationService->dispatchBlockJobsForGroupedComments(
            account: $this->record,
            commentsByUser: $commentsByUser
        );

        Notification::make()
            ->title('Queued block jobs for '.count($commentsByUser).' commenter(s)')
            ->success()
            ->send();

        $this->selectedCommenters = [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_comments')
                ->label('Back to Comments')
                ->icon('heroicon-o-arrow-left')
                ->url(AccountResource::getUrl('comments', [
                    'record' => $this->record,
                    'post' => $this->postId,
                ])),
            Action::make('block_selected_commenters')
                ->label('Block Selected Commenters')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Block selected commenters?')
                ->modalSubmitActionLabel('Block Commenters')
                ->action(fn () => $this->blockSelectedCommenters())
                ->disabled(fn () => empty($this->selectedCommenters)),
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->comments = $this->getCommentsFromApi();
                }),
        ];
    }

    public function getCommenters(): Collection
    {
        return $this->commentModerationService->getUniqueCommenters(collect($this->comments));
    }
}
