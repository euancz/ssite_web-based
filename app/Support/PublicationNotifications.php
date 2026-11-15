<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class PublicationNotifications
{
    /** Called after the public detail page has authorized access to this post. */
    public static function read(?User $user, Model $post): void
    {
        if (! $user || ! $post->isApproved() || $post->isArchived()) return;

        $user->unreadNotifications()
            ->whereRaw(NotificationSql::field('type').' = ?', ['published'])
            ->whereRaw(NotificationSql::field('post_type').' = ?', [$post::POST_TYPE])
            ->whereRaw(NotificationSql::field('post_id').' = ?', [(string) $post->getKey()])
            ->update(['read_at' => now()]);
    }
}
