<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

/** Activity permissions follow configured officer positions; advisers and superadmins never manage content. */
class ActivityPolicy
{
    public function viewAny(?User $user): bool { return true; }

    public function view(?User $user, Activity $activity): bool
    {
        return ($activity->isApproved() && $activity->content_status === Activity::CONTENT_ACTIVE)
            || ($user !== null && ($user->isPresident() || $this->owns($user, $activity)));
    }

    public function create(User $user): bool { return $user->canPostIn('activities'); }
    public function update(User $user, Activity $activity): bool { return $user->isPresident() || ($user->canPostIn('activities') && $this->owns($user, $activity)); }
    public function archive(User $user, Activity $activity): bool { return $this->update($user, $activity); }
    public function restore(User $user, Activity $activity): bool { return $this->update($user, $activity); }
    public function approve(User $user, Activity $activity): bool { return $user->canReview(); }
    public function reject(User $user, Activity $activity): bool { return $user->canReview(); }
    public function delete(User $user, Activity $activity): bool { return $user->canPermanentlyDelete(); }
    public function bulkArchive(User $user): bool { return $user->isPresident(); }

    // SECURITY: Compare post ownership with the custom users.user_id key.
    private function owns(User $user, Activity $activity): bool
    {
        return $activity->created_by_user_id !== null && (int) $activity->created_by_user_id === (int) $user->user_id;
    }
}
