@extends('layouts.app')
@section('title', 'Edit Liquidation Report')
@section('content')
<div class="dashboard-page workspace-composer-page"><x-ui.page-header title="Edit liquidation report" subtitle="Update report details or replace the PDF." /><section class="dashboard-panel workspace-composer-panel" aria-label="Edit liquidation report form">
    {{-- SECURITY: The update endpoint verifies President capability or ownership before saving. --}}
    @include('liquidation._form', ['liquidation' => $liquidation, 'action' => route('liquidations.update', $liquidation), 'submitLabel' => 'Save Changes'])
</section></div>
@endsection
