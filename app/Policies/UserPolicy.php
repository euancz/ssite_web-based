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

        if (! $actor->canManageOfficers() && ! $actor->canManageAdvisers()) {
            return false;
        }

        if ($target->isSuperadmin()) {
            return false;
        }

        if (! in_array($target->role, $actor->assignableRoles(), true)) {
            return false;
        }

        if ($target->isAdviser() && ! $actor->canManageAdvisers()) {
            return false;
        }

        return true;
    }
}
