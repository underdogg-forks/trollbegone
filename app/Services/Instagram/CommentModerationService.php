<?php

namespace App\Services\Instagram;

use App\Jobs\BlockUserJob;
use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * CommentModerationService handles comment-to-user moderation transformations.
 *
 * Keeps Filament pages thin by encapsulating commenter selection/grouping logic
 * and queue dispatch responsibilities.
 */
class CommentModerationService
{
    /**
     * Return first comment per selected username.
     *
     * @param  Collection<int, array<string, mixed>>  $comments
     * @param  array<int, string>  $selectedCommenters
     * @return Collection<string, array<string, mixed>>
     */
    public function getCommentsGroupedBySelectedUsernames(
        Collection $comments,
        array $selectedCommenters
    ): Collection {
        if (empty($selectedCommenters) || $comments->isEmpty()) {
            return collect();
        }

        $selectedCommentersLookup = array_flip($selectedCommenters);

        return $comments
            ->filter(function (array $comment) use ($selectedCommentersLookup): bool {
                if (! isset($comment['username']) || $comment['username'] === '') {
                    return false;
                }

                return isset($selectedCommentersLookup[$comment['username']]);
            })
            ->groupBy('username')
            ->map(fn (Collection $items): array => $items->first());
    }

    /**
     * Queue blocking jobs from grouped commenter comments.
     *
     * @param  Collection<string, array<string, mixed>>  $commentsByUser
     */
    public function dispatchBlockJobsForGroupedComments(
        Account $account,
        Collection $commentsByUser,
        string $reason = 'Blocked from commenters list'
    ): void {
        foreach ($commentsByUser as $username => $comment) {
            BlockUserJob::dispatch(
                account: $account,
                username: (string) $username,
                reason: $reason,
                commentText: $comment['text'] ?? null
            );
        }
    }

    /**
     * Build unique commenters summary for display.
     *
     * @param  Collection<int, array<string, mixed>>  $comments
     * @return Collection<int, array{username: string, comment_count: int, latest_comment: mixed}>
     */
    public function getUniqueCommenters(Collection $comments): Collection
    {
        return $comments
            ->filter(fn (array $comment): bool => isset($comment['username']))
            ->groupBy('username')
            ->map(function (Collection $items, string $username): array {
                $firstComment = $items->first();

                return [
                    'username' => $username,
                    'comment_count' => $items->count(),
                    'latest_comment' => $firstComment['text'] ?? null,
                ];
            })
            ->values();
    }
}
