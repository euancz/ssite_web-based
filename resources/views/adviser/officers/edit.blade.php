@extends('layouts.app')
@section('title', 'Edit Officer')
@section('content')
<div class="dashboard-page"><x-ui.page-header title="Edit Officer" subtitle="Update this year's officer snapshot." />
    @include('adviser.officers._form', ['action' => route('adviser.officers.update', $officer), 'method' => 'PATCH'])
</div>
@endsection
