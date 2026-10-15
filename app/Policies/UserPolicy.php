<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Restrict role changes to the actor's allowed assignment set and to the matrix rules.
     */
    public function changeRole(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        if (! $actor->canAssignOfficers() && ! $actor->canAssignAdvisers()) {
            return false;
        }

        if ($target->isSuperadmin()) {
            return false;
        }

        $targetRoles = $actor->canAssignAdvisers()
            ? [User::ROLE_STUDENT, User::ROLE_OFFICER, User::ROLE_ADVISER]
            : [User::ROLE_STUDENT, User::ROLE_OFFICER];

        if (! in_array($target->role, $targetRoles, true)) {
            return false;
        }

        if ($target->isAdviser() && ! $actor->canAssignAdvisers()) {
            return false;
        }

        return true;
    }
}
