@extends('layouts.app')

@section('title', 'New Achievement')

@section('content')
<div class="dashboard-page workspace-composer-page" data-post-composer data-composer-submit="{{ auth()->user()->postsAutoApproved() ? 'Publish post' : 'Submit for review' }}" data-composer-hint="{{ auth()->user()->postsAutoApproved() ? 'Your post will be published when you submit.' : 'Your post will be sent for approval before it is published.' }}">
    {{-- The shared heading and form panel follow Articles dashboard spacing. --}}
    <x-ui.page-header title="Create achievement" subtitle="Prepare an achievement for the SSITE community." />
    <x-workspace.composer-steps :step="2" />
    <p class="profile-field-help"><a href="{{ route('workspace.create') }}">Back to content types</a></p>
    <section class="dashboard-panel workspace-composer-panel" aria-label="Achievement form">
        {{-- SECURITY: The controller authorizes the role and sets ownership and status server-side. --}}
        @include('achievements._form', ['achievement' => null, 'action' => route('achievements.store'), 'submitLabel' => 'Create Achievement'])
    </section>
</div>
@endsection
