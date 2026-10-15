<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Document;
use App\Models\Liquidation;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;

/**
 * Registers Microsoft Socialite support and the position abilities used by authorization and navigation.
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
        Gate::define('review-posts', fn (User $user): bool => $user->canReview());
        Gate::define('manage-officers', fn (User $user): bool => $user->canManageOfficers());
        Gate::define('manage-advisers', fn (User $user): bool => $user->canAssignAdvisers());
        Gate::define('manage-users', fn (User $user): bool => $user->canAssignOfficers() || $user->canAssignAdvisers());

        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();
            $rows = $user ? $user->notifications()->whereNull('read_at')
                ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) AS notification_type, JSON_UNQUOTE(JSON_EXTRACT(data, '$.post_type')) AS post_type, COUNT(*) AS total")
                ->groupBy('notification_type', 'post_type')->get() : collect();
            // SECURITY: One grouped query supplies this user's nav dots and bell count; review notices never make tab dots.
            $view->with('notificationNavCounts', $rows->where('notification_type', 'published')->pluck('total', 'post_type'));
            $view->with('notificationUnreadCount', (int) $rows->sum('total'));
            $pendingReviewCount = 0;
            if ($user?->canReview()) {
                foreach ([Article::class, Activity::class, Achievement::class, Document::class, Liquidation::class] as $postModel) {
                    $pendingReviewCount += $postModel::pending()->count();
                }
            }
            $view->with('pendingReviewCount', $pendingReviewCount);
        });

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
