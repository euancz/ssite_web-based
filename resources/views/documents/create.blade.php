@extends('layouts.app')
@section('title', 'Upload Document')
@section('content')
<div class="dashboard-page"><x-ui.page-header title="Upload Document" subtitle="Add a PDF for the SSITE community." /><section class="dashboard-panel">
    {{-- SECURITY: The controller assigns ownership and workflow fields after validating the PDF upload. --}}
    @include('documents._form', ['document' => null, 'action' => route('documents.store'), 'submitLabel' => 'Upload Document'])
</section></div>
@endsection
