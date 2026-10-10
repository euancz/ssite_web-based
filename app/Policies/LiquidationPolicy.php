<?php

namespace App\Policies;

use App\Models\Liquidation;
use App\Models\User;

/** Keeps liquidation visibility, file delivery, and workflow actions role and owner scoped. */
class LiquidationPolicy
{
    public function viewAny(?User $user): bool { return true; }
    public function view(?User $user, Liquidation $liquidation): bool
    {
        return ($liquidation->isApproved() && $liquidation->content_status === Liquidation::CONTENT_ACTIVE)
            || ($user !== null && ($user->isAdviser() || $this->owns($user, $liquidation)));
    }
    public function viewFile(User $user, Liquidation $liquidation): bool { return $this->view($user, $liquidation); }
    public function download(User $user, Liquidation $liquidation): bool { return $this->view($user, $liquidation); }
    public function create(User $user): bool { return $user->canPost(); }
    public function update(User $user, Liquidation $liquidation): bool { return $user->isAdviser() || $this->owns($user, $liquidation); }
    public function archive(User $user, Liquidation $liquidation): bool { return $this->update($user, $liquidation); }
    public function restore(User $user, Liquidation $liquidation): bool { return $this->update($user, $liquidation); }
    public function approve(User $user, Liquidation $liquidation): bool { return $user->isAdviser(); }
    public function reject(User $user, Liquidation $liquidation): bool { return $user->isAdviser(); }
    public function delete(User $user, Liquidation $liquidation): bool { return $user->isAdviser(); }
    public function bulkArchive(User $user): bool { return $user->isAdviser(); }

    // SECURITY: Compare ownership with users.user_id; deleted authors remain manageable by advisers.
    private function owns(User $user, Liquidation $liquidation): bool
    {
        return $liquidation->created_by_user_id !== null && (int) $liquidation->created_by_user_id === (int) $user->user_id;
    }
}
