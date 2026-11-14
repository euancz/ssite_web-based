<?php

namespace App\Observers;

use App\Services\MobilePublisher;
use Illuminate\Database\Eloquent\Model;

final class MobilePublicationObserver
{
    public function saved(Model $post): void
    {
        if (config('mobile.sync_enabled')) app(MobilePublisher::class)->publish($post);
    }

    public function deleted(Model $post): void
    {
        if (config('mobile.sync_enabled')) app(MobilePublisher::class)->remove($post);
    }
}
