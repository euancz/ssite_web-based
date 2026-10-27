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
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);
        $versions = DB::table('content_versions')->where('post_type', $record::POST_TYPE)
            ->where('post_id', $record->getKey())->orderByDesc('id')->paginate(10);

        return view('workspace.content-versions', compact('record', 'module', 'versions'));
    }
}
