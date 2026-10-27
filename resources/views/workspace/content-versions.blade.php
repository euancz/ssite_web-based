@extends('layouts.app')
@section('title', 'Content version history')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Content version history" :subtitle="$record->title" />
    <section class="dashboard-panel">
        <p>Recorded text and metadata, newest first. File contents and earlier unrecorded edits are not retained. Viewing a version does not restore or publish it.</p>
        <p>A baseline captures the existing content when tracking first encounters an edit; its time is the capture time, not the original editing time.</p>
        <a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.posts.'.$module.'.show', $record) }}">Back to submission</a>
        @forelse ($versions as $version)
            <details class="workspace-content-version">
                <summary>{{ ucfirst($version->kind) }} · {{ \Illuminate\Support\Carbon::parse($version->created_at)->format('M j, Y g:i A') }} · {{ $version->editor_name ?: 'Editor not recorded' }}</summary>
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
