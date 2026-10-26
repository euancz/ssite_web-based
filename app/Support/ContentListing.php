<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ContentListing
{
    /** Apply the visibility boundary before filters, counts, or pagination run. */
    public function scope(Builder $query, Request $request, string $module): array
    {
        $workspace = $request->routeIs('workspace.posts.*', 'editorial.*');
        $user = $request->user();
        $allowedTabs = ['published'];
        $pendingCount = null;
        if ($workspace) {
            abort_unless($user && $user->canPostIn($module), 403);
            if (! $user->isPresident()) {
                $query->where('created_by_user_id', $user->user_id);
            }
            $allowedTabs = ['mine', 'published', 'pending', 'rejected', 'archived'];
            if ($user->canReview()) {
                $allowedTabs[] = 'all';
                $pendingCount = (clone $query)->pending()->count();
            }
        }
        $default = $workspace && ! $user->isPresident() ? 'mine' : 'published';
        $tab = $request->query('tab', $default);
        if (! is_string($tab) || ! in_array($tab, $allowedTabs, true)) {
            $tab = $default;
        }
        match ($tab) {
            'published' => $query->publiclyVisible(),
            'mine' => $query->where('created_by_user_id', $user->user_id),
            'pending' => $query->pending(),
            'rejected' => $query->rejected(),
            'archived' => $query->archived(),
            default => $query,
        };

        return [$query, $allowedTabs, $tab, $pendingCount, $workspace];
    }
}
