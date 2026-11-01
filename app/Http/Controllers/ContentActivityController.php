<?php

namespace App\Http\Controllers;

use App\Services\ContentActivityLog;
use App\Services\ContentReviewHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ContentActivityController extends Controller
{
    public function __invoke(Request $request, ContentActivityLog $activity, ContentReviewHistory $history): View
    {
        $modules = $history->modulesFor($request->user());
        abort_if($modules === [], 403);
        $filters = $request->validate([
            'days' => ['nullable', 'integer', Rule::in([7, 30])],
            'module' => ['nullable', Rule::in(array_keys($modules))],
            'action' => ['nullable', Rule::in(['posted', 'approved', 'edited', 'rejected'])],
            'q' => ['nullable', 'string', 'max:100'],
            'post' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
        ]);
        $filters['days'] = (int) ($filters['days'] ?? 30);
        $filters['module'] = $filters['module'] ?? '';
        $filters['action'] = $filters['action'] ?? '';
        $filters['q'] = trim($filters['q'] ?? '');
        $filters['post'] = $filters['post'] ?? null;
        $filters['from'] = $filters['from'] ?? '';
        $filters['to'] = $filters['to'] ?? '';
        $filters['sort'] = $filters['sort'] ?? 'newest';
        if ($filters['from'] || $filters['to']) {
            $filters['start'] = Carbon::createFromFormat('!Y-m-d', $filters['from'] ?: now()->subDays(29)->toDateString());
            $filters['end'] = Carbon::createFromFormat('!Y-m-d', $filters['to'] ?: now()->toDateString())->endOfDay();
        }
        $log = $activity->forUser($request->user(), $filters);

        return view('workspace.content-activity', compact('modules', 'filters', 'log'));
    }
}
