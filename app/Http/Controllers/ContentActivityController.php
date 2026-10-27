<?php

namespace App\Http\Controllers;

use App\Services\ContentActivity;
use App\Services\ContentReviewHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentActivityController extends Controller
{
    public function __invoke(Request $request, ContentActivity $activity, ContentReviewHistory $history): View
    {
        $modules = $history->modulesFor($request->user());
        abort_if($modules === [], 403);
        $filters = $request->validate([
            'days' => ['nullable', 'integer', Rule::in([7, 30])],
            'module' => ['nullable', Rule::in(array_keys($modules))],
        ]);
        $filters['days'] = (int) ($filters['days'] ?? 30);
        $filters['module'] = $filters['module'] ?? '';
        $report = $activity->forUser($request->user(), $filters['days'], $filters['module'] ?: null);

        return view('workspace.content-activity', compact('modules', 'filters', 'report'));
    }
}
