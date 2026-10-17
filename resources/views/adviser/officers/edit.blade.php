@extends('layouts.app')
@section('title', 'Edit Officer')
@section('content')
<div class="dashboard-page president-officers-page"><x-ui.page-header title="Edit officer" subtitle="Update this year's officer snapshot on the public About page." />
    @include('adviser.officers._form', ['action' => route('adviser.officers.update', $officer), 'method' => 'PATCH'])
</div>
@endsection
