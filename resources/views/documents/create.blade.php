@extends('layouts.app')
@section('title', 'Upload Document')
@section('content')
<div class="dashboard-page workspace-composer-page"><x-ui.page-header title="Create document" subtitle="Add a PDF and its details for review." /><section class="dashboard-panel workspace-composer-panel" aria-label="Document form">
    {{-- SECURITY: The controller assigns ownership and workflow fields after validating the PDF upload. --}}
    @include('documents._form', ['document' => null, 'action' => route('documents.store'), 'submitLabel' => 'Upload Document'])
</section></div>
@endsection
