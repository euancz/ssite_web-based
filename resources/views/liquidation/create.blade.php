@extends('layouts.app')
@section('title', 'Upload Liquidation Report')
@section('content')
<div class="dashboard-page workspace-composer-page" data-post-composer data-composer-submit="{{ auth()->user()->postsAutoApproved() ? 'Publish post' : 'Submit for review' }}" data-composer-hint="{{ auth()->user()->postsAutoApproved() ? 'Your post will be published when you submit.' : 'Your post will be sent for approval before it is published.' }}"><x-ui.page-header title="Create liquidation report" subtitle="Add the report PDF and its accounting details for review." />
    <x-workspace.composer-steps :step="2" />
    <x-workspace.back-to-content-types />
    <section class="dashboard-panel workspace-composer-panel" aria-label="Liquidation report form">
    {{-- SECURITY: The controller assigns ownership and approval state after validating the upload. --}}
    @include('liquidation._form', ['liquidation' => null, 'action' => route('liquidations.store'), 'submitLabel' => 'Upload Report'])
</section></div>
@endsection
