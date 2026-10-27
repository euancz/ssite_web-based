@extends('layouts.app')
@section('title', 'Content version history')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Content version history" :subtitle="$record->title" />
    <x-ui.form-errors />
    @if ($comparison)
        <section class="dashboard-panel workspace-version-comparison" aria-labelledby="version-comparison-title">
            <h2 id="version-comparison-title">Changes in version #{{ $comparison['selected']->id }}</h2>
            @if ($comparison['previous'])
                <p>Comparing version #{{ $comparison['previous']->id }} with version #{{ $comparison['selected']->id }}. Only changed fields are shown; text formatting is displayed as source.</p>
                <p>Earlier: {{ \Illuminate\Support\Carbon::parse($comparison['previous']->created_at)->format('M j, Y g:i A') }} · {{ $comparison['previous']->editor_name ?: 'Editor not recorded' }}<br>
                    Selected: {{ \Illuminate\Support\Carbon::parse($comparison['selected']->created_at)->format('M j, Y g:i A') }} · {{ $comparison['selected']->editor_name ?: 'Editor not recorded' }}</p>
                @forelse ($comparison['changes'] as $change)
                    <section class="workspace-version-change">
                        <h3>{{ \Illuminate\Support\Str::headline($change['field']) }}</h3>
                        <dl>
                            <div><dt>Before</dt><dd>{{ ! $change['had_field'] ? 'Not recorded' : ($change['before'] === null ? 'Not set' : ($change['before'] === '' ? 'Empty text' : $change['before'])) }}</dd></div>
                            <div><dt>After</dt><dd>{{ ! $change['has_field'] ? 'Not recorded' : ($change['after'] === null ? 'Not set' : ($change['after'] === '' ? 'Empty text' : $change['after'])) }}</dd></div>
                        </dl>
                    </section>
                @empty
                    <p>No text or metadata differences between these versions.</p>
                @endforelse
            @else
                <p>This is the first recorded version. There is no earlier version to compare.</p>
            @endif
            <a href="{{ route('workspace.content-versions.index', ['module' => $module, 'post' => $record->getKey(), 'page' => $versions->currentPage()]) }}">Close comparison</a>
        </section>
    @endif
    <section class="dashboard-panel">
        <p>Recorded text and metadata, newest first. File contents and earlier unrecorded edits are not retained. Viewing a version does not restore or publish it.</p>
        <p>A baseline captures the existing content when tracking first encounters an edit; its time is the capture time, not the original editing time.</p>
        <a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.posts.'.$module.'.show', $record) }}">Back to submission</a>
        @forelse ($versions as $version)
            <details class="workspace-content-version">
                <summary>#{{ $version->id }} · {{ ucfirst($version->kind) }} · {{ \Illuminate\Support\Carbon::parse($version->created_at)->format('M j, Y g:i A') }} · {{ $version->editor_name ?: 'Editor not recorded' }}</summary>
                <p><a href="{{ route('workspace.content-versions.index', ['module' => $module, 'post' => $record->getKey(), 'compare' => $version->id, 'page' => $versions->currentPage()]) }}#version-comparison-title">Compare with previous version</a></p>
                <dl>
                    @foreach (json_decode($version->snapshot, true, 512, JSON_THROW_ON_ERROR) as $field => $value)
                        <dt>{{ \Illuminate\Support\Str::headline($field) }}</dt>
                        <dd>{{ $value === null || $value === '' ? 'Not set' : $value }}</dd>
                    @endforeach
                </dl>
            </details>
        @empty
            <p>No versions recorded yet. Versions start with the next text or metadata edit.</p>
        @endforelse
        {{ $versions->links() }}
    </section>
</div>
@endsection
