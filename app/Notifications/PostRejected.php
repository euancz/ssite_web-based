<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Database-only notice sent only to the author with the adviser's rejection reason. */
class PostRejected extends Notification
{
    public function __construct(private readonly array $payload) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge($this->payload, ['type' => 'rejected', 'message' => 'Your post was rejected.']);
    }
}
