<?php

namespace App\Services;

use App\Models\User;
use App\Support\ContentModules;

final class ContentDashboard
{
    public function forUser(User $user): array
    {
        $moduleStats = [];
        $dashboardStatus = array_fill_keys(['pending', 'published', 'rejected', 'archived'], 0);
        $recentPosts = collect();
        $rejectedPosts = collect();
        $pendingPosts = collect();

        foreach (ContentModules::all() as $key => $module) {
            if (! $user->canPostIn($key)) {
                continue;
            }
            $owned = $module['model']::query();
            if (! $user->canReview()) {
                $owned->where('created_by_user_id', $user->user_id);
            }

            // One aggregate per permitted module; archived approval states remain separate.
            $counts = (clone $owned)->selectRaw("COUNT(*) as total,
                COALESCE(SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN approval_status = 'approved' AND content_status = 'active' THEN 1 ELSE 0 END), 0) as published,
                COALESCE(SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END), 0) as rejected,
                COALESCE(SUM(CASE WHEN content_status = 'archived' THEN 1 ELSE 0 END), 0) as archived")->first();
            $moduleStats[$key] = ['label' => $module['label'], 'index' => 'workspace.posts.'.$key.'.index', 'create' => $key.'.create'];
            foreach (['total', 'pending', 'published', 'rejected', 'archived'] as $status) {
                $moduleStats[$key][$status] = (int) $counts->$status;
                if (array_key_exists($status, $dashboardStatus)) {
                    $dashboardStatus[$status] += (int) $counts->$status;
                }
            }

            $decorate = function ($post) use ($key, $module) {
                $post->setAttribute('type', $module['singular']);
                $post->setAttribute('status', $post->approval_status);
                $post->setAttribute('preview_url', route('workspace.posts.'.$key.'.show', $post));

                return $post;
            };
            $keyName = $owned->getModel()->getKeyName();
            if ($user->canReview()) {
                $pendingPosts = $pendingPosts->concat((clone $owned)->pending()->with('author')
                    ->orderBy('created_at')->orderBy($keyName)->limit(5)->get()->map($decorate));
            } else {
                $recentPosts = $recentPosts->concat((clone $owned)->latest('created_at')->orderByDesc($keyName)->limit(10)->get()->map($decorate));
                $rejectedPosts = $rejectedPosts->concat((clone $owned)->rejected()->with('reviewer')
                    ->orderByDesc('reviewed_at')->orderByDesc($keyName)->limit(5)->get()->map($decorate));
            }
        }

        return [
            'draftCount' => app(ContentDrafts::class)->owned($user)->count(),
            'moduleStats' => $moduleStats,
            'dashboardStatus' => $dashboardStatus,
            'recentPosts' => $recentPosts->sortByDesc('created_at')->take(10)->values(),
            'rejectedPosts' => $rejectedPosts->sortByDesc('reviewed_at')->take(5)->values(),
            'pendingPosts' => $pendingPosts->sortBy('created_at')->take(5)->values(),
        ];
    }
}
