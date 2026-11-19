@extends('layouts.app')

@section('title', 'New Activity')

@section('content')
<div class="dashboard-page workspace-composer-page" data-post-composer data-composer-submit="{{ auth()->user()->postsAutoApproved() ? 'Publish post' : 'Submit for review' }}" data-composer-hint="{{ auth()->user()->postsAutoApproved() ? 'Your post will be published when you submit.' : 'Your post will be sent for approval before it is published.' }}">
    {{-- The shared heading and form panel follow Articles dashboard spacing. --}}
    <x-ui.page-header title="Create activity" subtitle="Prepare an activity announcement for review." />
    <x-workspace.composer-steps :step="2" />
    <x-workspace.back-to-content-types />
    <section class="dashboard-panel workspace-composer-panel" aria-label="Activity form">
        {{-- SECURITY: The controller authorizes the role and sets ownership and status server-side. --}}
        @include('activities._form', ['activity' => null, 'action' => route('activities.store'), 'submitLabel' => 'Create Activity'])
    </section>
</div>
@endsection
