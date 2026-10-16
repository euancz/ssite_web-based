<?php

namespace App\Policies;

use App\Models\Achievement;
use App\Models\User;

/** Achievement permissions follow configured officer positions; advisers and superadmins never manage content. */
class AchievementPolicy
{
    public function viewAny(?User $user): bool { return true; }

    public function view(?User $user, Achievement $achievement): bool
    {
        return ($achievement->isApproved() && in_array($achievement->content_status, [Achievement::CONTENT_ACTIVE, Achievement::CONTENT_ARCHIVED], true))
            || ($user !== null && ($user->isPresident() || $this->owns($user, $achievement)));
    }

    public function create(User $user): bool { return $user->canPostIn('achievements'); }
    public function update(User $user, Achievement $achievement): bool { return $user->isPresident() || ($user->canPostIn('achievements') && $this->owns($user, $achievement)); }
    public function archive(User $user, Achievement $achievement): bool { return $this->update($user, $achievement); }
    public function restore(User $user, Achievement $achievement): bool { return $this->update($user, $achievement); }
    public function approve(User $user, Achievement $achievement): bool { return $user->canReview(); }
    public function reject(User $user, Achievement $achievement): bool { return $user->canReview(); }
    public function delete(User $user, Achievement $achievement): bool { return $user->canPermanentlyDelete(); }
    public function bulkArchive(User $user): bool { return $user->isPresident(); }

    // SECURITY: Compare post ownership with the custom users.user_id key.
    private function owns(User $user, Achievement $achievement): bool
    {
        return $achievement->created_by_user_id !== null && (int) $achievement->created_by_user_id === (int) $user->user_id;
    }
}
