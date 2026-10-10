@extends('layouts.app')
@section('title', 'Upload Liquidation Report')
@section('content')
<div class="dashboard-page"><x-ui.page-header title="Upload Liquidation Report" subtitle="Add a PDF report for review." /><section class="dashboard-panel">
    {{-- SECURITY: The controller assigns ownership and approval state after validating the upload. --}}
    @include('liquidation._form', ['liquidation' => null, 'action' => route('liquidations.store'), 'submitLabel' => 'Upload Report'])
</section></div>
@endsection
