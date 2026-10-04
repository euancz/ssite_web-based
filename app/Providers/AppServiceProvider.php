<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;

/**
 * Registers Microsoft Socialite support and the role abilities used by shared navigation.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    /**
     * Connect the Microsoft driver and centralize role checks used by Blade navigation.
     */
    public function boot(): void
    {
        // These abilities control navigation visibility; protected routes enforce access separately.
        Gate::define('view-post-dashboard', fn (User $user): bool => $user->canPost());
        Gate::define('review-posts', fn (User $user): bool => $user->isAdviser());
        Gate::define('manage-users', fn (User $user): bool => $user->isAdviser());

        // SocialiteProviders requires its Microsoft driver to be extended when the package event fires.
        Event::listen(
            SocialiteWasCalled::class,
            function (SocialiteWasCalled $event) {
                $event->extendSocialite(
                    'microsoft',
                    MicrosoftProvider::class
                );
            }
        );
    }
}