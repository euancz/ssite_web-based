<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Database-only notice for active, approved posts; the author is excluded from this broadcast. */
class PostPublished extends Notification
{
    public function __construct(private readonly array $payload) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge($this->payload, ['type' => 'published', 'message' => self::message($this->payload['post_type'] ?? '')]);
    }

    public static function message(string $postType): string
    {
        $label = in_array($postType, ['article', 'activity', 'achievement', 'document', 'liquidation'], true)
            ? $postType : 'post';

        return "A new {$label} has been published.";
    }
}
