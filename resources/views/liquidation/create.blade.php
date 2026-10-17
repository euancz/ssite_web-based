@extends('layouts.app')
@section('title', 'Upload Liquidation Report')
@section('content')
<div class="dashboard-page workspace-composer-page"><x-ui.page-header title="Create liquidation report" subtitle="Add the report PDF and its accounting details for review." /><section class="dashboard-panel workspace-composer-panel" aria-label="Liquidation report form">
    {{-- SECURITY: The controller assigns ownership and approval state after validating the upload. --}}
    @include('liquidation._form', ['liquidation' => null, 'action' => route('liquidations.store'), 'submitLabel' => 'Upload Report'])
</section></div>
@endsection
