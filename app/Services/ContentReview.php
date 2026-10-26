<?php

namespace App\Services;

use App\Models\User;
use App\Support\PostNotificationRecipients;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ContentReview
{
    /** Serialize review with edits and dispatch notifications only for a committed decision. */
    public function decide(Model $post, User $reviewer, bool $approve, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($post, $reviewer, $approve, $reason): bool {
            $current = $post->newQuery()->whereKey($post->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($reviewer)->authorize($approve ? 'approve' : 'reject', $current);
            if (! $current->isPending()) {
                return false;
            }
            foreach ($post->getRawOriginal() as $key => $value) {
                if (in_array($key, ['view_count', 'like_count'], true)) {
                    continue;
                }
                if ($current->getRawOriginal($key) !== $value) {
                    return false;
                }
            }
            $current->approval_status = $approve ? 'approved' : 'rejected';
            $current->rejection_reason = $approve ? null : $reason;
            $current->reviewed_by_user_id = $reviewer->user_id;
            $current->reviewed_at = now();
            $current->saveOrFail();
            $post->setRawAttributes($current->getAttributes(), true);
            DB::afterCommit(function () use ($current, $reviewer, $approve, $reason): void {
                $notifications = app(PostNotificationRecipients::class);
                if ($approve) {
                    $notifications->approved($current::POST_TYPE, $current, $reviewer);
                } else {
                    $notifications->rejected($current::POST_TYPE, $current, $reviewer, $reason);
                }
            });

            return true;
        });
    }
}
