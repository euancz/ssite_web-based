@extends('layouts.landing')

@section('title', 'Terms & Privacy | SSITE')

@section('content')
<section class="faq-section wrap" id="questions" aria-labelledby="policy-title">
    <div class="policy-intro">
        <h1 id="policy-title">Terms &amp; privacy</h1>
    </div>
    <div class="faq-list">
        @include('pages.partials.policies', ['expanded' => true])
    </div>
</section>
@endsection
