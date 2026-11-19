@extends('layouts.app')
@section('title', 'Upload Document')
@section('content')
<div class="dashboard-page workspace-composer-page" data-post-composer data-composer-submit="{{ auth()->user()->postsAutoApproved() ? 'Publish post' : 'Submit for review' }}" data-composer-hint="{{ auth()->user()->postsAutoApproved() ? 'Your post will be published when you submit.' : 'Your post will be sent for approval before it is published.' }}"><x-ui.page-header title="Create document" subtitle="Add a PDF and its details for review." />
    <x-workspace.composer-steps :step="2" />
    <x-workspace.back-to-content-types />
    <section class="dashboard-panel workspace-composer-panel" aria-label="Document form">
    {{-- SECURITY: The controller assigns ownership and workflow fields after validating the PDF upload. --}}
    @include('documents._form', ['document' => null, 'action' => route('documents.store'), 'submitLabel' => 'Upload Document'])
</section></div>
@endsection
