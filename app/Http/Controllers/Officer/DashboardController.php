<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Document;
use App\Models\Liquidation;
use Illuminate\Contracts\View\View;

/** Supplies President review counts or position-scoped officer post summaries. */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();

        if ($user->isPresident()) {
            $workspaceModules = [
                'articles' => ['label' => 'Article', 'icon' => 'file', 'model' => Article::class],
                'activities' => ['label' => 'Activity', 'icon' => 'calendar', 'model' => Activity::class],
                'achievements' => ['label' => 'Achievement', 'icon' => 'award', 'model' => Achievement::class],
                'documents' => ['label' => 'Document', 'icon' => 'file', 'model' => Document::class],
                'liquidations' => ['label' => 'Liquidation report', 'icon' => 'chart', 'model' => Liquidation::class],
            ];
            $postableModules = collect($user->postableModules())->filter(fn (string $key): bool => isset($workspaceModules[$key]));
            $availableModules = $postableModules->map(fn (string $key): array => ['key' => $key] + $workspaceModules[$key] + [
                'index' => $key . '.index',
                'create' => $key . '.create',
            ])->values();
            $dashboardStatus = ['pending' => 0, 'published' => 0, 'archived' => 0];

            foreach ($workspaceModules as $module) {
                $model = $module['model'];
                $dashboardStatus['pending'] += $model::pending()->count();
                $dashboardStatus['published'] += $model::publiclyVisible()->count();
                $dashboardStatus['archived'] += $model::archived()->count();
            }

            return view('workspace.dashboard', compact('availableModules', 'dashboardStatus'));
        }

        $modules = [
            'articles' => ['label' => 'Articles', 'singular' => 'article', 'icon' => 'file', 'model' => Article::class, 'index' => 'articles.index', 'create' => 'articles.create', 'show' => 'articles.show'],
            'activities' => ['label' => 'Activities', 'singular' => 'activity', 'icon' => 'calendar', 'model' => Activity::class, 'index' => 'activities.index', 'create' => 'activities.create', 'show' => 'activities.show'],
            'achievements' => ['label' => 'Achievements', 'singular' => 'achievement', 'icon' => 'award', 'model' => Achievement::class, 'index' => 'achievements.index', 'create' => 'achievements.create', 'show' => 'achievements.show'],
            'documents' => ['label' => 'Documents', 'singular' => 'document', 'icon' => 'file', 'model' => Document::class, 'index' => 'documents.index', 'create' => 'documents.create', 'show' => 'documents.show'],
            'liquidations' => ['label' => 'Liquidation', 'singular' => 'liquidation report', 'icon' => 'chart', 'model' => Liquidation::class, 'index' => 'liquidations.index', 'create' => 'liquidations.create', 'show' => 'liquidations.show'],
        ];
        $user = request()->user();
        $pendingByModule = [];
        $pendingPosts = collect();
        $moduleStats = [];
        $rejectedPosts = collect();
        $recentPosts = collect();

        if ($user->canReview()) {
            foreach ($modules as $key => $module) {
                $model = $module['model'];
                $pendingByModule[$key] = ['label' => $module['label'], 'count' => $model::pending()->count()];
                $rows = $model::pending()->with('author')->latest('created_at')->limit(5)->get();
                foreach ($rows as $post) {
                    $post->setAttribute('type', $module['label']);
                    $post->setAttribute('status', $post->approval_status);
                    $post->setAttribute('review_url', route($module['show'], $post));
                    $pendingPosts->push($post);
                }
            }
            $pendingPosts = $pendingPosts->sortByDesc('created_at')->take(10)->values();
        } else {
            foreach ($user->postableModules() as $key) {
                if (! isset($modules[$key])) continue;
                $module = $modules[$key];
                $model = $module['model'];
                $owned = $model::query()->where('created_by_user_id', $user->user_id);
                $moduleStats[$key] = [
                    'label' => $module['label'],
                    'index' => $module['index'],
                    'create' => $module['create'],
                    'total' => (clone $owned)->count(),
                    'pending' => (clone $owned)->where('approval_status', 'pending')->count(),
                    'approved' => (clone $owned)->where('approval_status', 'approved')->count(),
                    'rejected' => (clone $owned)->where('approval_status', 'rejected')->count(),
                ];
                foreach ((clone $owned)->with('author')->latest('created_at')->limit(5)->get() as $post) {
                    $post->setAttribute('type', $module['label']);
                    $post->setAttribute('status', $post->approval_status);
                    $post->setAttribute('preview_url', route($module['show'], $post));
                    $recentPosts->push($post);
                }
                foreach ((clone $owned)->where('approval_status', 'rejected')->latest('created_at')->limit(5)->get() as $post) {
                    $post->setAttribute('type', $module['label']);
                    $post->setAttribute('status', $post->approval_status);
                    $post->setAttribute('preview_url', route($module['show'], $post));
                    $rejectedPosts->push($post);
                }
            }
            $recentPosts = $recentPosts->sortByDesc('created_at')->take(10)->values();
            $rejectedPosts = $rejectedPosts->sortByDesc('created_at')->take(5)->values();
        }

        return view('officer.dashboard', compact('modules', 'pendingByModule', 'pendingPosts', 'moduleStats', 'recentPosts', 'rejectedPosts'));
    }
}
