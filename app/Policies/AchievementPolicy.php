<?php

namespace App\Policies;

use App\Models\Achievement;
use App\Models\User;

/** Keeps achievement visibility and state changes tied to roles and ownership. */
class AchievementPolicy
{
    /** Anyone may open the list; the controller filters rows by role and tab. */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /** Public achievements are visible to all; private records are limited to author or adviser. */
    public function view(?User $user, Achievement $achievement): bool
    {
        return ($achievement->isApproved() && $achievement->content_status === Achievement::CONTENT_ACTIVE)
            || ($user !== null && ($user->isAdviser() || $this->owns($user, $achievement)));
    }

    /** Officers and advisers may create achievements. */
    public function create(User $user): bool
    {
        return $user->canPost();
    }

    /** The adviser or owning account may edit achievement content. */
    public function update(User $user, Achievement $achievement): bool
    {
        return $user->isAdviser() || $this->owns($user, $achievement);
    }

    /** The adviser or owning account may archive an achievement. */
    public function archive(User $user, Achievement $achievement): bool
    {
        return $user->isAdviser() || $this->owns($user, $achievement);
    }

    /** The adviser or owning account may restore an achievement. */
    public function restore(User $user, Achievement $achievement): bool
    {
        return $user->isAdviser() || $this->owns($user, $achievement);
    }

    /** Only advisers may approve achievements. */
    public function approve(User $user, Achievement $achievement): bool
    {
        return $user->isAdviser();
    }

    /** Only advisers may reject achievements and record a reason. */
    public function reject(User $user, Achievement $achievement): bool
    {
        return $user->isAdviser();
    }

    /** Only advisers may permanently remove an achievement and its stored image. */
    public function delete(User $user, Achievement $achievement): bool
    {
        return $user->isAdviser();
    }

    /** Only advisers may archive a validated group of achievements. */
    public function bulkArchive(User $user): bool
    {
        return $user->isAdviser();
    }

    /** Compare ownership with users.user_id, never Laravel's conventional id field. */
    private function owns(User $user, Achievement $achievement): bool
    {
        return $achievement->created_by_user_id !== null
            && (int) $achievement->created_by_user_id === (int) $user->user_id;
    }
}
