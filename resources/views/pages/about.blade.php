@inject('siteContent', 'App\Support\SiteContent')
@extends('layouts.app')

@section('title', 'About Us')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/about.css') }}?v={{ filemtime(public_path('css/about.css')) }}">
@endpush

@section('content')
<div class="about-page site-page">
	<x-ui.stories-banner
            edit-section="about"
		:eyebrow="$siteContent->value('about', 'eyebrow')"
		:title="$siteContent->value('about', 'title')"
		:description="$siteContent->value('about', 'description')"
		labelledby="about-hero-title"
	/>

	<section class="mission-vision-grid" aria-label="SSITE mission and vision">
		<article class="mission-card">
			<h2>{{ $siteContent->value('about-mission', 'heading') }} <x-section-pencil section="about-mission" /></h2>
			<p>{{ $siteContent->value('about-mission', 'description') }}</p>
		</article>
		<article class="vision-card">
			<h2>{{ $siteContent->value('about-vision', 'heading') }} <x-section-pencil section="about-vision" /></h2>
			<p>{{ $siteContent->value('about-vision', 'description') }}</p>
		</article>
	</section>

	<section class="officers-section" aria-labelledby="officers-heading">
		<div class="officer-heading-row">
			<h2 id="officers-heading" class="about-section-title">{{ $siteContent->value('about-officers', 'roster_heading') }} {{ $current }} <x-section-pencil section="about-officers" /></h2>
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
		<h2 id="leadership-heading" class="about-section-title">{{ $siteContent->value('about-officers', 'history_heading') }}</h2>
		<div class="leadership-list">
			@foreach ($history as $year => $yearOfficers)
				<details class="leadership-item" @if ($loop->first) open @endif>
					<summary>
						<span>{{ $siteContent->value('about-officers', 'roster_heading') }} {{ $year }}</span>
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
