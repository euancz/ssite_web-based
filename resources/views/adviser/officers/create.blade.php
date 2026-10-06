@extends('layouts.app')
@section('title', 'Add Officer')
@section('content')
<div class="dashboard-page"><x-ui.page-header title="Add Officer" subtitle="Add a yearly officer snapshot." />
    @include('adviser.officers._form', ['action' => route('adviser.officers.store'), 'method' => 'POST'])
</div>
@endsection
