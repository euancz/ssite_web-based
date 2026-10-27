<?php

namespace App\Http\Controllers;

use App\Services\ContentVersions;
use App\Support\ContentModules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ContentVersionController extends Controller
{
    public function __invoke(Request $request, string $module, string $post, ContentVersions $history)
    {
        $definition = ContentModules::all()[$module] ?? abort(404);
        $record = $definition['model']::findOrFail($post);
        Gate::authorize('update', $record);
        abort_unless($history->available(), 404);
        $filters = $request->validate(['page' => ['sometimes', 'integer', 'min:1'], 'compare' => ['sometimes', 'integer', 'min:1']]);
        $query = DB::table('content_versions')->where('post_type', $record::POST_TYPE)
            ->where('post_id', $record->getKey());
        $comparison = null;
        if (isset($filters['compare'])) {
            // Scope both lookups to the authorized post, including the previous version.
            $selected = (clone $query)->where('id', $filters['compare'])->first();
            abort_unless($selected, 404);
            $previous = (clone $query)->where('id', '<', $selected->id)->orderByDesc('id')->first();
            $comparison = ['selected' => $selected, 'previous' => $previous,
                'changes' => $previous ? $history->changes($previous->snapshot, $selected->snapshot) : []];
        }
        $versions = $query->orderByDesc('id')->paginate(10)->appends($request->only('compare'));

        return view('workspace.content-versions', compact('record', 'module', 'versions', 'comparison'));
    }
}
