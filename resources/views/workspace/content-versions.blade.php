@extends('layouts.app')
@section('title', 'Content version history')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Content version history" :subtitle="$record->title" />
    <x-ui.form-errors />
    @if ($comparison)
        <section class="dashboard-panel workspace-version-comparison" aria-labelledby="version-comparison-title">
            <h2 id="version-comparison-title">Compare versions</h2>
            @if ($comparison['previous'])
                <p>Version #{{ $comparison['previous']->id }} &rarr; Version #{{ $comparison['selected']->id }}</p>
                @forelse ($comparison['changes'] as $change)
                    <section class="workspace-version-change">
                        <h3>{{ \Illuminate\Support\Str::headline($change['field']) }}</h3>
                        <dl>
                            <div><dt>Older version</dt><dd>{{ $change['before'] === null || $change['before'] === '' ? 'No content' : $change['before'] }}</dd></div>
                            <div><dt>Selected version</dt><dd>{{ $change['after'] === null || $change['after'] === '' ? 'No content' : $change['after'] }}</dd></div>
                        </dl>
                    </section>
                @empty
                    <p>No content changes between these versions.</p>
                @endforelse
            @else
                <p>There is no earlier version to compare.</p>
            @endif
            <a href="{{ route('workspace.content-versions.index', ['module' => $module, 'post' => $record->getKey(), 'page' => $versions->currentPage()]) }}">Close comparison</a>
        </section>
    @endif
    <section class="dashboard-panel">
        <a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.posts.'.$module.'.show', $record) }}">Back to submission</a>
        <x-ui.data-table label="Saved content versions">
            <table class="dashboard-table">
                <thead><tr><th scope="col">Version</th><th scope="col">Content</th><th scope="col">Saved by</th><th scope="col">Date</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    @forelse ($versions as $version)
                        @php($snapshot = json_decode($version->snapshot, true, 512, JSON_THROW_ON_ERROR))
                        <tr>
                            <th scope="row">#{{ $version->id }}</th>
                            <td>
                                <details class="workspace-content-version">
                                    <summary>{{ $snapshot['title'] ?? 'Untitled' }}</summary>
                                    <dl>
                                        <dt>{{ array_key_exists('content', $snapshot) ? 'Content' : 'Description' }}</dt>
                                        <dd>{{ $snapshot['content'] ?? $snapshot['description'] ?? 'No content' }}</dd>
                                    </dl>
                                </details>
                            </td>
                            <td>{{ $version->editor_name ?: 'Unknown' }}</td>
                            <td><time datetime="{{ \Illuminate\Support\Carbon::parse($version->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($version->created_at)->format('M j, Y g:i A') }}</time></td>
                            <td><a href="{{ route('workspace.content-versions.index', ['module' => $module, 'post' => $record->getKey(), 'compare' => $version->id, 'page' => $versions->currentPage()]) }}#version-comparison-title">Compare with older version</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No saved versions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.data-table>
        {{ $versions->links('vendor.pagination.ssite') }}
    </section>
</div>
@endsection