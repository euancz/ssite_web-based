<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

/**
 * Keeps activity visibility and state changes tied to roles and activity ownership.
 */
class ActivityPolicy
{
    /** Anyone may open the list; the controller filters rows by role and tab. */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /** Public activities are visible to all; private activities are limited to their author or adviser. */
    public function view(?User $user, Activity $activity): bool
    {
        return ($activity->isApproved() && $activity->content_status === Activity::CONTENT_ACTIVE)
            || ($user !== null && ($user->isAdviser() || $this->owns($user, $activity)));
    }

    /** Officers and advisers may create activities. */
    public function create(User $user): bool
    {
        return $user->canPost();
    }

    /** The adviser or owning account may edit activity content. */
    public function update(User $user, Activity $activity): bool
    {
        return $user->isAdviser() || $this->owns($user, $activity);
    }

    /** The adviser or owning account may archive an activity. */
    public function archive(User $user, Activity $activity): bool
    {
        return $user->isAdviser() || $this->owns($user, $activity);
    }

    /** The adviser or owning account may restore an activity. */
    public function restore(User $user, Activity $activity): bool
    {
        return $user->isAdviser() || $this->owns($user, $activity);
    }

    /** Only advisers may approve activities. */
    public function approve(User $user, Activity $activity): bool
    {
        return $user->isAdviser();
    }

    /** Only advisers may reject activities and record a reason. */
    public function reject(User $user, Activity $activity): bool
    {
        return $user->isAdviser();
    }

    /** Only advisers may permanently remove an activity and its stored image. */
    public function delete(User $user, Activity $activity): bool
    {
        return $user->isAdviser();
    }

    /** Only advisers may archive a validated group of activities. */
    public function bulkArchive(User $user): bool
    {
        return $user->isAdviser();
    }

    /** Compare ownership with users.user_id, never Laravel's conventional id field. */
    private function owns(User $user, Activity $activity): bool
    {
        return $activity->created_by_user_id !== null
            && (int) $activity->created_by_user_id === (int) $user->user_id;
    }
}
