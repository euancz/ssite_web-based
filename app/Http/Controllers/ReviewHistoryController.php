<?php

namespace App\Http\Controllers;

use App\Services\ContentReviewHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReviewHistoryController extends Controller
{
    public function __invoke(Request $request, ContentReviewHistory $history): View
    {
        $modules = $history->modulesFor($request->user());
        abort_if($modules === [], 403);
        abort_unless($history->available(), 404);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'required_with:post', Rule::in(array_keys($modules))],
            'post' => ['nullable', 'integer', 'min:1'],
            'decision' => ['nullable', Rule::in(['approved', 'rejected'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
        ]);
        $query = $history->visibleQuery($request->user());
        if ($filters['module'] ?? null) {
            $query->where('events.post_type', $modules[$filters['module']]['model']::POST_TYPE);
        }
        if ($filters['post'] ?? null) {
            $query->where('events.post_id', $filters['post']);
        }
        if ($filters['decision'] ?? null) {
            $query->where('events.decision', $filters['decision']);
        }
        $filters['q'] = trim($filters['q'] ?? '');
        if ($filters['q'] !== '') {
            // The only searched field is the historical title; request text stays a bound value.
            $query->where('events.post_title', 'like', '%'.$filters['q'].'%');
        }
        if ($filters['from'] ?? null) {
            $query->where('events.created_at', '>=', Carbon::createFromFormat('!Y-m-d', $filters['from']));
        }
        if ($filters['to'] ?? null) {
            $query->where('events.created_at', '<', Carbon::createFromFormat('!Y-m-d', $filters['to'])->addDay());
        }
        $filters['sort'] = $filters['sort'] ?? 'newest';
        $direction = $filters['sort'] === 'oldest' ? 'asc' : 'desc';
        $events = $query->orderBy('events.created_at', $direction)->orderBy('events.id', $direction)
            ->paginate(20)->appends(array_filter($filters, fn ($value) => $value !== null && $value !== ''));
        $types = [];
        foreach ($modules as $key => $module) {
            $types[$module['model']::POST_TYPE] = ['key' => $key, 'label' => $module['label']];
        }

        return view('workspace.review-history', compact('events', 'filters', 'modules', 'types'));
    }
}
