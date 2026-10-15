<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/** Document permissions follow configured officer positions while preserving private PDF authorization. */
class DocumentPolicy
{
    public function viewAny(?User $user): bool { return true; }

    public function view(?User $user, Document $document): bool
    {
        return ($document->isApproved() && $document->content_status === Document::CONTENT_ACTIVE)
            || ($user !== null && ($user->isPresident() || $this->owns($user, $document)));
    }

    public function viewFile(User $user, Document $document): bool { return $this->view($user, $document); }
    public function download(User $user, Document $document): bool { return $this->view($user, $document); }
    public function create(User $user): bool { return $user->canPostIn('documents'); }
    public function update(User $user, Document $document): bool { return $user->isPresident() || ($user->canPostIn('documents') && $this->owns($user, $document)); }
    public function archive(User $user, Document $document): bool { return $this->update($user, $document); }
    public function restore(User $user, Document $document): bool { return $this->update($user, $document); }
    public function approve(User $user, Document $document): bool { return $user->canReview(); }
    public function reject(User $user, Document $document): bool { return $user->canReview(); }
    public function delete(User $user, Document $document): bool { return $user->canPermanentlyDelete(); }
    public function bulkArchive(User $user): bool { return $user->isPresident(); }

    // SECURITY: Ownership always compares created_by_user_id with users.user_id.
    private function owns(User $user, Document $document): bool
    {
        return $document->created_by_user_id !== null && (int) $document->created_by_user_id === (int) $user->user_id;
    }
}
