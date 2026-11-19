@extends('layouts.app')

@section('title', 'About Us')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/about.css') }}?v={{ filemtime(public_path('css/about.css')) }}">
@endpush

@section('content')
<div class="about-page site-page">
	<x-ui.stories-banner
		eyebrow="About the organization"
		title="Get to know SSITE"
		description="Discover who we are, what we stand for, and the vision that brings our community of aspiring IT professionals together."
		labelledby="about-hero-title"
	/>

	<section class="mission-vision-grid" aria-label="SSITE mission and vision">
		<article class="mission-card">
			<h2>Mission</h2>
			<p>SSITE provides hands-on, research-based, and values-centered learning experiences, equipping students with the skills needed to excel in both local and global IT industries while fulfilling their social responsibilities.</p>
		</article>
		<article class="vision-card">
			<h2>Vision</h2>
			<p>SSITE aims to cultivate competitive Information Technology professionals capable of thriving in both local and international IT industries while upholding ethical standards.</p>
		</article>
	</section>

	<section class="officers-section" aria-labelledby="officers-heading">
		<div class="officer-heading-row">
			<h2 id="officers-heading" class="about-section-title">SSITE Officers A.Y. {{ $current }}</h2>
		</div>
		{{-- Officer snapshots stay independent of later changes to user roles and profiles. --}}
		<div class="officers-grid">
			@forelse ($officers as $officer)
				@include('pages.partials.officer-card', ['officer' => $officer, 'reveal' => true])
			@empty
				<div class="about-officers-empty"><p>Officers for A.Y. {{ $current }} will be announced soon.</p></div>
			@endforelse
		</div>
	</section>

	@if ($history->isNotEmpty())
	<section class="leadership-section" aria-labelledby="leadership-heading">
		<h2 id="leadership-heading" class="about-section-title">Leadership History</h2>
		<div class="leadership-list">
			@foreach ($history as $year => $yearOfficers)
				<details class="leadership-item" @if ($loop->first) open @endif>
					<summary>
						<span>SSITE Officers A.Y. {{ $year }}</span>
						<x-icon name="chevron-down" size="sm" class="leadership-chevron" />
					</summary>
					<div class="officers-grid history-grid">
						@foreach ($yearOfficers as $officer)
							@include('pages.partials.officer-card', ['officer' => $officer])
						@endforeach
					</div>
				</details>
			@endforeach
		</div>
	</section>
	@endif
</div>
@endsection
