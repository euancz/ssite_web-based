<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveContentDraftRequest;
use App\Services\ContentDrafts;
use App\Support\ContentModules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContentDraftController extends Controller
{
    public function index(Request $request, ContentDrafts $service)
    {
        abort_unless($request->user()->canPost(), 403);
        $modules = array_filter(ContentModules::all(), fn ($module) => $request->user()->can('create', $module['model']));
        $filters = $request->validate(['module' => ['nullable', Rule::in(array_keys($modules))]]);
        $drafts = $service->owned($request->user())->when($filters['module'] ?? null, fn ($query, $module) => $query->where('module', $module))
            ->latest('updated_at')->orderBy('draft_id')->paginate(10)->appends($filters);

        return view('workspace.drafts.index', compact('drafts', 'modules', 'filters'));
    }

    public function edit(Request $request, string $draft, ContentDrafts $service)
    {
        $draft = $service->owned($request->user())->whereKey($draft)->firstOrFail();
        $draftValues = $draft->payload;
        $view = $draft->module === 'liquidations' ? 'liquidation' : $draft->module;

        return view($view.'.create', compact('draft', 'draftValues'));
    }

    public function save(SaveContentDraftRequest $request, string $module, ContentDrafts $service)
    {
        $draft = $service->save($request->user(), $module, $request->validated());

        return redirect()->route('workspace.drafts.edit', $draft)->with('status', 'Draft saved. Text and details are saved; images and PDFs must be selected again before submitting.');
    }

    public function destroy(Request $request, string $draft, ContentDrafts $service)
    {
        $values = $request->validate(['draft_version' => ['required', 'integer', 'min:1']]);
        DB::transaction(function () use ($request, $draft, $service, $values) {
            $record = $service->owned($request->user())->whereKey($draft)->lockForUpdate()->firstOrFail();
            $service->checkVersion($record, (int) $values['draft_version']);
            $record->delete();
        });

        return redirect()->route('workspace.drafts.index')->with('status', 'Draft deleted.');
    }
}
