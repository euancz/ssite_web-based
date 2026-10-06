@extends('layouts.app')

@section('title', 'About Us')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/about.css') }}">
@endpush

@section('content')
<div class="about-page">
	<h1 class="about-page-title">Get to Know SSITE</h1>

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
		<h2 id="officers-heading" class="about-section-title">SSITE Officers A.Y. {{ $current }}</h2>
		{{-- Year-specific snapshots keep the public list stable when user profiles change. --}}
		<div class="officers-grid">
			@forelse ($officers as $officer)
				<article class="officer-card">
					@if ($officer->photoUrl())
						<img class="officer-photo" src="{{ $officer->photoUrl() }}" alt="Officer portrait for {{ $officer->name }}">
					@else
						<div class="officer-photo officer-photo-placeholder" role="img" aria-label="Photo unavailable">Photo</div>
					@endif
					<h3>{{ $officer->name }}</h3>
					<p>{{ $officer->position }}</p>
				</article>
			@empty
				<p>Officers for A.Y. {{ $current }} will be announced soon.</p>
			@endforelse
		</div>
	</section>

	@if ($history->isNotEmpty())
	<section class="leadership-section" aria-labelledby="leadership-heading">
		<h2 id="leadership-heading" class="about-section-title">Leadership History</h2>

		<div class="leadership-list">
			@foreach ($history as $year => $yearOfficers)
				<details class="leadership-item">
					<summary>
						<span>SSITE Officers A.Y. {{ $year }}</span>
						<svg class="leadership-chevron" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
						</svg>
					</summary>
					<div class="officers-grid">
						@foreach ($yearOfficers as $officer)
							<article class="officer-card">
								@if ($officer->photoUrl())
									<img class="officer-photo" src="{{ $officer->photoUrl() }}" alt="Officer portrait for {{ $officer->name }}">
								@else
									<div class="officer-photo officer-photo-placeholder" role="img" aria-label="Photo unavailable">Photo</div>
								@endif
								<h3>{{ $officer->name }}</h3><p>{{ $officer->position }}</p>
							</article>
						@endforeach
					</div>
				</details>
			@endforeach
		</div>
	</section>
	@endif
</div>
@endsection
