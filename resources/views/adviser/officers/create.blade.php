@extends('layouts.app')
@section('title', 'Add Officer')
@section('content')
<div class="dashboard-page president-officers-page"><x-ui.page-header title="Add officer" subtitle="Add a yearly officer snapshot to the public About page." />
    @include('adviser.officers._form', ['action' => route('adviser.officers.store'), 'method' => 'POST'])
</div>
@endsection
