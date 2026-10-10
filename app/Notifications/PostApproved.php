<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Database-only notice sent to the author separately from the publication broadcast. */
class PostApproved extends Notification
{
    public function __construct(private readonly array $payload) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge($this->payload, ['type' => 'approved', 'message' => 'Your post was approved.']);
    }
}
