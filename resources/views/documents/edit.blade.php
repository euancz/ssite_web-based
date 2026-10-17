@extends('layouts.app')
@section('title', 'Edit Document')
@section('content')
<div class="dashboard-page workspace-composer-page"><x-ui.page-header title="Edit document" subtitle="Update document details or replace the PDF." /><section class="dashboard-panel workspace-composer-panel" aria-label="Edit document form">
    {{-- SECURITY: The update endpoint verifies President capability or ownership before saving changes. --}}
    @include('documents._form', ['document' => $document, 'action' => route('documents.update', $document), 'submitLabel' => 'Save Changes'])
</section></div>
@endsection
