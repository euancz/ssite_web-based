@extends('layouts.app')
@section('title', 'My drafts')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="My drafts" subtitle="Private work in progress. Resume a draft, attach any files, then submit when it is ready." />
    <x-ui.form-errors />
    @if (session('status'))<p class="dashboard-success" role="status">{{ session('status') }}</p>@endif
    <form method="GET" action="{{ route('workspace.drafts.index') }}" class="dashboard-filter-form">
        <div class="dashboard-form-field"><label for="draft-module">Content area</label>
            <select id="draft-module" name="module"><option value="">All assigned areas</option>
                @foreach ($modules as $key => $module)<option value="{{ $key }}" @selected(($filters['module'] ?? '') === $key)>{{ $module['label'] }}</option>@endforeach
            </select>
        </div>
        <button class="dashboard-button" type="submit">Apply filter</button>
    </form>
    <section class="dashboard-panel">
        <p>{{ $drafts->total() }} {{ \Illuminate\Support\Str::plural('draft', $drafts->total()) }} in your currently assigned content areas.</p>
        <x-ui.data-table label="Your private drafts">
            <table class="dashboard-table"><thead><tr><th scope="col">Title</th><th scope="col">Area</th><th scope="col">Last saved</th><th scope="col">Actions</th></tr></thead>
                <tbody>@forelse ($drafts as $draft)
                    <tr><th scope="row">{{ ($draft->payload['title'] ?? '') ?: 'Untitled draft' }}</th><td>{{ $modules[$draft->module]['label'] }}</td><td>{{ $draft->updated_at?->format('M j, Y g:i A') }}</td>
                        <td><a class="dashboard-text-button" href="{{ route('workspace.drafts.edit', $draft) }}">Resume<span class="visually-hidden"> {{ ($draft->payload['title'] ?? '') ?: 'untitled draft' }}</span></a>
                            <details class="draft-delete"><summary>Delete draft</summary>
                                <p>Permanently delete this draft?</p>
                                <form method="POST" action="{{ route('workspace.drafts.destroy', $draft) }}">@csrf @method('DELETE')
                                    <input type="hidden" name="draft_version" value="{{ $draft->lock_version }}">
                                    <button type="submit" class="dashboard-button dashboard-button-secondary">Confirm deletion</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty<tr><td colspan="4">No drafts in this view. Use Save draft on a create form to keep unfinished text and details.</td></tr>@endforelse</tbody>
            </table>
        </x-ui.data-table>
        {{ $drafts->links('vendor.pagination.ssite') }}
    </section>
</div>
@endsection
