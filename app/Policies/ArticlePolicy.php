<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

/** Article permissions follow configured officer positions; advisers and superadmins never manage content. */
class ArticlePolicy
{
    public function viewAny(?User $user): bool { return true; }

    public function view(?User $user, Article $article): bool
    {
        return ($article->isApproved() && in_array($article->content_status, [Article::CONTENT_ACTIVE, Article::CONTENT_ARCHIVED], true))
            || ($user !== null && ($user->isPresident() || $this->owns($user, $article)));
    }

    public function create(User $user): bool { return $user->canPostIn('articles'); }
    public function update(User $user, Article $article): bool { return $user->isPresident() || ($user->canPostIn('articles') && $this->owns($user, $article)); }
    public function archive(User $user, Article $article): bool { return $this->update($user, $article); }
    public function restore(User $user, Article $article): bool { return $this->update($user, $article); }
    public function approve(User $user, Article $article): bool { return $user->canReview(); }
    public function reject(User $user, Article $article): bool { return $user->canReview(); }
    public function delete(User $user, Article $article): bool { return $user->canPermanentlyDelete(); }
    public function bulkArchive(User $user): bool { return $user->isPresident(); }

    // SECURITY: Compare post ownership with the custom users.user_id key.
    private function owns(User $user, Article $article): bool
    {
        return $article->created_by_user_id !== null && (int) $article->created_by_user_id === (int) $user->user_id;
    }
}
