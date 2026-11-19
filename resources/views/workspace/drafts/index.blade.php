@extends('layouts.app')
@section('title', 'My work - My drafts')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="My work" subtitle="Private work in progress. Resume a draft, attach any files, then submit when it is ready.">
        <x-slot:actions><a class="dashboard-button" href="{{ route('workspace.create') }}"><x-workspace.icon name="plus" /> Create post</a></x-slot:actions>
    </x-ui.page-header>
    <x-workspace.ui.my-work-tabs :module="$filters['module'] ?? null" />
    <x-ui.form-errors />
    @if (session('status'))<p class="dashboard-success" role="status">{{ session('status') }}</p>@endif
    <form method="GET" action="{{ route('workspace.drafts.index') }}" class="workspace-list-toolbar">
        <div class="workspace-list-field"><label for="draft-module">Content type</label>
            <select id="draft-module" name="module" onchange="this.form.requestSubmit()"><option value="">All assigned areas</option>
                @foreach ($modules as $key => $module)<option value="{{ $key }}" @selected(($filters['module'] ?? '') === $key)>{{ $module['label'] }}</option>@endforeach
            </select>
        </div>
        <div class="workspace-list-filter-actions">
            @if (($filters['module'] ?? '') !== '' || $filters['sort'] !== 'recent')
                <a class="dashboard-text-button" href="{{ route('workspace.drafts.index') }}">Clear filters</a>
            @endif
            <x-posts.filter-panel :active-count="(int) ($filters['sort'] !== 'recent')">
                <div class="site-filter-field">
                    <label for="draft-sort">Sort by</label>
                    <select id="draft-sort" name="sort">
                        <option value="recent" @selected($filters['sort'] === 'recent')>Recently saved first</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest saved first</option>
                    </select>
                </div>
            </x-posts.filter-panel>
        </div>
    </form>
    <section class="dashboard-panel">
        <p>{{ $drafts->total() }} {{ \Illuminate\Support\Str::plural('draft', $drafts->total()) }} in your currently assigned content areas.</p>
        <x-ui.data-table label="Your private drafts">
            <table class="dashboard-table"><thead><tr><th scope="col">Title</th><th scope="col">Area</th><th scope="col">Last saved</th><th scope="col">Actions</th></tr></thead>
                <tbody>@forelse ($drafts as $draft)
                    @php($draftTitle = ($draft->payload['title'] ?? '') ?: 'Untitled draft')
                    <tr><th scope="row">{{ $draftTitle }}</th><td>{{ $modules[$draft->module]['label'] }}</td><td>{{ $draft->updated_at?->format('M j, Y g:i A') }}</td>
                        <td>
                            <x-ui.table-actions id="draft-actions-{{ $draft->id }}" label="Actions for {{ $draftTitle }}">
                                <a href="{{ route('workspace.drafts.edit', $draft) }}">Resume<span class="visually-hidden"> {{ $draftTitle }}</span></a>
                                <button type="button" data-post-confirm-open="delete-draft-{{ $draft->id }}">Delete draft</button>
                            </x-ui.table-actions>
                            <form id="delete-draft-form-{{ $draft->id }}" method="POST" action="{{ route('workspace.drafts.destroy', $draft) }}">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="draft_version" value="{{ $draft->lock_version }}">
                            </form>
                            <x-posts.confirm-action
                                id="delete-draft-{{ $draft->id }}"
                                title="Delete draft?"
                                message="This permanently deletes the draft."
                                form-id="delete-draft-form-{{ $draft->id }}"
                                button-text="Delete draft"
                            />
                        </td>
                    </tr>
                @empty<tr><td colspan="4">No drafts in this view. Use Save draft on a create form to keep unfinished text and details.</td></tr>@endforelse</tbody>
            </table>
        </x-ui.data-table>
        {{ $drafts->links('vendor.pagination.ssite') }}
    </section>
</div>
@endsection
