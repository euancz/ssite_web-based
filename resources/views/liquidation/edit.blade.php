@extends('layouts.app')
@section('title', 'Edit Liquidation Report')
@section('content')
<div class="dashboard-page"><x-ui.page-header title="Edit Liquidation Report" subtitle="Update report details or replace the PDF." /><section class="dashboard-panel">
    {{-- SECURITY: The update endpoint verifies adviser role or ownership before saving. --}}
    @include('liquidation._form', ['liquidation' => $liquidation, 'action' => route('liquidations.update', $liquidation), 'submitLabel' => 'Save Changes'])
</section></div>
@endsection
