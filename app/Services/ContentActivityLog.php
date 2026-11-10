<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ContentActivityLog
{
    public function forUser(User $viewer, array $filters): array
    {
        $history = app(ContentReviewHistory::class);
        $modules = $history->modulesFor($viewer);
        abort_if($modules === [], 403);
        $historyAvailable = $history->available();
        $editsAvailable = app(ContentVersions::class)->available();
        $end = $filters['end'] ?? Carbon::now();
        $start = $filters['start'] ?? $end->copy()->startOfDay()->subDays($filters['days'] - 1);
        $union = null;
        // Legacy content and newer audit tables can have different MySQL collations.
        // Normalize only the result expressions; no stored data or schema is changed.
        $text = static fn (string $expression): string => in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? "CONVERT($expression USING utf8mb4) COLLATE utf8mb4_unicode_ci"
            : (DB::connection()->getDriverName() === 'pgsql' ? "CAST($expression AS text)" : $expression);
        $title = $text('posts.title');
        $author = $text('authors.name');
        $posted = $text("'posted'");
        $module = $text('?');
        $reviewTitle = $text('events.post_title');
        $reviewer = $text('events.reviewer_name');
        $decision = $text('events.decision');
        $editor = $text('versions.editor_name');
        $edited = $text("'edited'");
        $noReason = $text('NULL');
        $reason = $text('events.reason');
        foreach ($modules as $key => $metadata) {
            if ($filters['module'] && $filters['module'] !== $key) {
                continue;
            }
            $model = new $metadata['model'];
            $posts = DB::table($model->getTable().' as posts');
            if (! $viewer->isPresident()) {
                $posts->where('posts.created_by_user_id', $viewer->getKey());
            }
            $queries = [(clone $posts)->leftJoin('users as authors', 'authors.id', '=', 'posts.created_by_user_id')
                ->whereBetween('posts.created_at', [$start, $end])
                ->selectRaw("posts.{$model->getKeyName()} as post_id, $title as title, $author as actor, $posted as action, posts.created_at as occurred_at, $module as module, 0 as event_id, $noReason as reason", [$key])];
            if ($historyAvailable) {
                $queries[] = (clone $posts)->join('content_review_events as events', 'events.post_id', '=', 'posts.'.$model->getKeyName())
                    ->where('events.post_type', $model::POST_TYPE)->whereBetween('events.created_at', [$start, $end])
                    ->selectRaw("events.post_id, $reviewTitle as title, $reviewer as actor, $decision as action, events.created_at as occurred_at, $module as module, events.id as event_id, $reason as reason", [$key]);
            }
            if ($editsAvailable) {
                $queries[] = (clone $posts)->join('content_versions as versions', 'versions.post_id', '=', 'posts.'.$model->getKeyName())
                    ->where('versions.post_type', $model::POST_TYPE)->where('versions.kind', 'edited')->whereBetween('versions.created_at', [$start, $end])
                    ->selectRaw("versions.post_id, $title as title, $editor as actor, $edited as action, versions.created_at as occurred_at, $module as module, versions.id as event_id, $noReason as reason", [$key]);
            }
            foreach ($queries as $query) {
                $union = $union ? $union->unionAll($query) : $query;
            }
        }
        $query = DB::query()->fromSub($union, 'activity');
        if ($filters['q'] !== '') {
            $query->where('title', 'like', '%'.$filters['q'].'%');
        }
        if ($filters['post']) {
            $query->where('post_id', $filters['post']);
        }
        if ($filters['action']) {
            $query->where('action', $filters['action']);
        }
        $direction = $filters['sort'] === 'oldest' ? 'asc' : 'desc';
        $events = $query->orderBy('occurred_at', $direction)->orderBy('module')->orderBy('post_id', $direction)
            ->orderBy('action')->orderByDesc('event_id')->paginate(25)->withQueryString();

        return compact('events', 'historyAvailable', 'editsAvailable');
    }
}
