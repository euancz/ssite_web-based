<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Database-only notice; pending items go only to advisers and are never announced as published. */
class PostSubmittedForReview extends Notification
{
    public function __construct(private readonly array $payload, private readonly bool $needsReviewAgain = false) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Re-edits keep the review audience but explain that an earlier decision needs another look.
        return array_merge($this->payload, [
            'type' => 'submitted',
            'message' => $this->needsReviewAgain ? 'A post needs review again.' : 'A new post was submitted for review.',
        ]);
    }
}
