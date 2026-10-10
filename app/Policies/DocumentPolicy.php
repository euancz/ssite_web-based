<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/** Keeps document visibility, file access, and state changes role and owner scoped. */
class DocumentPolicy
{
    public function viewAny(?User $user): bool { return true; }
    public function view(?User $user, Document $document): bool
    {
        return ($document->isApproved() && $document->content_status === Document::CONTENT_ACTIVE)
            || ($user !== null && ($user->isAdviser() || $this->owns($user, $document)));
    }
    public function viewFile(User $user, Document $document): bool { return $this->view($user, $document); }
    public function download(User $user, Document $document): bool { return $this->view($user, $document); }
    public function create(User $user): bool { return $user->canPost(); }
    public function update(User $user, Document $document): bool { return $user->isAdviser() || $this->owns($user, $document); }
    public function archive(User $user, Document $document): bool { return $this->update($user, $document); }
    public function restore(User $user, Document $document): bool { return $this->update($user, $document); }
    public function approve(User $user, Document $document): bool { return $user->isAdviser(); }
    public function reject(User $user, Document $document): bool { return $user->isAdviser(); }
    public function delete(User $user, Document $document): bool { return $user->isAdviser(); }
    public function bulkArchive(User $user): bool { return $user->isAdviser(); }

    // SECURITY: Ownership always compares created_by_user_id with the custom users.user_id key.
    private function owns(User $user, Document $document): bool
    {
        return $document->created_by_user_id !== null && (int) $document->created_by_user_id === (int) $user->user_id;
    }
}
