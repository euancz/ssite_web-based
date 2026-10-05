<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

/**
 * Keeps article visibility and state changes tied to roles and article ownership.
 */
class ArticlePolicy
{
    /**
     * Allow anyone to open the listing; the controller filters its rows by role and tab.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Allow public articles to everyone and private articles only to their author or adviser.
     */
    public function view(?User $user, Article $article): bool
    {
        return ($article->isApproved()
                && $article->content_status === Article::CONTENT_ACTIVE)
            || ($user !== null && ($user->isAdviser() || $this->owns($user, $article)));
    }

    /**
     * Allow officers and advisers to create articles for organizational communication.
     */
    public function create(User $user): bool
    {
        return $user->canPost();
    }

    /**
     * Allow the adviser or the owning account to change article text and image.
     */
    public function update(User $user, Article $article): bool
    {
        return $user->isAdviser() || $this->owns($user, $article);
    }

    /**
     * Allow the adviser or the owning account to shelve an article.
     */
    public function archive(User $user, Article $article): bool
    {
        return $user->isAdviser() || $this->owns($user, $article);
    }

    /**
     * Allow the adviser or the owning account to restore an article.
     */
    public function restore(User $user, Article $article): bool
    {
        return $user->isAdviser() || $this->owns($user, $article);
    }

    /**
     * Allow only advisers to approve articles after review.
     */
    public function approve(User $user, Article $article): bool
    {
        return $user->isAdviser();
    }

    /**
     * Allow only advisers to reject articles and record a reason.
     */
    public function reject(User $user, Article $article): bool
    {
        return $user->isAdviser();
    }

    /**
     * Allow only advisers to permanently remove an article and its image.
     */
    public function delete(User $user, Article $article): bool
    {
        return $user->isAdviser();
    }

    /**
     * Allow only advisers to archive a validated selection of articles.
     */
    public function bulkArchive(User $user): bool
    {
        return $user->isAdviser();
    }

    /**
     * Compare ownership with the users table's user_id key, never the conventional id field.
     */
    private function owns(User $user, Article $article): bool
    {
        return $article->created_by_user_id !== null
            && (int) $article->created_by_user_id === (int) $user->user_id;
    }
}
