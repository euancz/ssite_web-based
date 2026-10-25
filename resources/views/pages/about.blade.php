@extends('layouts.app')

@section('title', 'About Us')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/about.css') }}">
@endpush

@section('content')
<div class="about-page">
	<section class="about-hero" aria-labelledby="about-hero-title">
		<p class="about-hero-label">ABOUT THE ORGANIZATION</p>
		<h1 id="about-hero-title">GET TO KNOW SSITE</h1>
		<p>Discover who we are, what we stand for, and the vision that brings our community together.</p>
	</section>

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
				<article class="officer-card card-hover" tabindex="0">
					@php
						$officerPhoto = null;
						if (is_object($officer) && method_exists($officer, 'photoUrl')) {
							$officerPhoto = $officer->photoUrl();
						} elseif (is_object($officer) && isset($officer->photo_url)) {
							$officerPhoto = $officer->photo_url;
						} elseif (is_array($officer) && isset($officer['photo_url'])) {
							$officerPhoto = $officer['photo_url'];
						}
					@endphp
					@if ($officerPhoto)
						<div class="officer-photo-frame card-media"><img class="officer-photo" src="{{ $officerPhoto }}" alt="Officer portrait for {{ $officer->name ?? ($officer['name'] ?? 'SSITE officer') }}"></div>
					@else
						<div class="officer-photo officer-photo-placeholder" role="img" aria-label="Photo unavailable">Photo</div>
					@endif
					<h3>{{ $officer->name ?? ($officer['name'] ?? 'Officer Name') }}</h3>
					<p>{{ $officer->position ?? ($officer['position'] ?? 'Officer Role') }}</p>
				</article>
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
							<article class="officer-card card-hover" tabindex="0">
								@php
									$yearOfficerPhoto = null;
									if (is_object($officer) && method_exists($officer, 'photoUrl')) {
										$yearOfficerPhoto = $officer->photoUrl();
									} elseif (is_object($officer) && isset($officer->photo_url)) {
										$yearOfficerPhoto = $officer->photo_url;
									} elseif (is_array($officer) && isset($officer['photo_url'])) {
										$yearOfficerPhoto = $officer['photo_url'];
									}
								@endphp
								@if ($yearOfficerPhoto)
									<div class="officer-photo-frame card-media"><img class="officer-photo" src="{{ $yearOfficerPhoto }}" alt="Officer portrait for {{ $officer->name ?? ($officer['name'] ?? 'SSITE officer') }}"></div>
								@else
									<div class="officer-photo officer-photo-placeholder" role="img" aria-label="Photo unavailable">Photo</div>
								@endif
								<h3>{{ $officer->name ?? ($officer['name'] ?? 'Officer Name') }}</h3>
								<p>{{ $officer->position ?? ($officer['position'] ?? 'Officer Role') }}</p>
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
