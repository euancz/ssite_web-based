<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Database notice delivered only to the author when another user adds a like. */
class PostLiked extends Notification
{
    public function __construct(private readonly array $payload) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge($this->payload, [
            'type' => 'liked',
            'message' => $this->payload['actor_name'].' liked your post.',
        ]);
    }
}
