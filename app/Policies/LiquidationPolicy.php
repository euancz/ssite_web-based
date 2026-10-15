<?php

namespace App\Policies;

use App\Models\Liquidation;
use App\Models\User;

/** Liquidation permissions follow configured positions; PDF records stay private behind their controller routes. */
class LiquidationPolicy
{
    public function viewAny(?User $user): bool { return true; }

    public function view(?User $user, Liquidation $liquidation): bool
    {
        return ($liquidation->isApproved() && $liquidation->content_status === Liquidation::CONTENT_ACTIVE)
            || ($user !== null && ($user->isPresident() || $this->owns($user, $liquidation)));
    }

    public function viewFile(User $user, Liquidation $liquidation): bool { return $this->view($user, $liquidation); }
    public function download(User $user, Liquidation $liquidation): bool { return $this->view($user, $liquidation); }
    public function create(User $user): bool { return $user->canPostIn('liquidations'); }
    public function update(User $user, Liquidation $liquidation): bool { return $user->isPresident() || ($user->canPostIn('liquidations') && $this->owns($user, $liquidation)); }
    public function archive(User $user, Liquidation $liquidation): bool { return $this->update($user, $liquidation); }
    public function restore(User $user, Liquidation $liquidation): bool { return $this->update($user, $liquidation); }
    public function approve(User $user, Liquidation $liquidation): bool { return $user->canReview(); }
    public function reject(User $user, Liquidation $liquidation): bool { return $user->canReview(); }
    public function delete(User $user, Liquidation $liquidation): bool { return $user->canPermanentlyDelete(); }
    public function bulkArchive(User $user): bool { return $user->isPresident(); }

    // SECURITY: Ownership always compares created_by_user_id with users.user_id.
    private function owns(User $user, Liquidation $liquidation): bool
    {
        return $liquidation->created_by_user_id !== null && (int) $liquidation->created_by_user_id === (int) $user->user_id;
    }
}
