<?php

namespace App\Contracts;

use App\Models\Account;
use Illuminate\Support\Collection;

interface InstagramApiServiceContract
{
    public function getFollowing(Account $account): Collection;

    public function getPostsByUsername(Account $account, string $username): Collection;

    public function getPostComments(Account $account, string $postId): Collection;

    public function filterCommentsByTag(Collection $comments, string $tag): Collection;

    public function filterCommentsWithoutTags(Collection $comments): Collection;

    public function deleteComment(Account $account, string $commentId): bool;

    public function getStories(Account $account): Collection;

    public function getStoryComments(Account $account, string $storyId): Collection;

    public function blockUser(Account $account, string $userId): bool;

    public function getUserInfo(Account $account, string $username): ?array;
}
