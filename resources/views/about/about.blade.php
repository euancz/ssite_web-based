@extends('layouts.app')

@section('title', 'About Us')

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
		<h2 id="officers-heading" class="about-section-title">SSITE Officers A.Y. 2026-2027</h2>

		@php
			$officers = [
				['Kyle Alegre', 'Adviser'],
				['Lance Carlo Bernabe', 'Student Adviser'],
				['Michaelle Vickeema Sarmiento', 'President'],
				['Irish Nicole Bernabe', 'Vice President (Internal)'],
				['Charlotte Sapnu', 'Vice President (External)'],
				['Lian San Diego', 'Secretary'],
				['John Daniel Bayani', 'Treasurer'],
				['Xyra Shannel Alvarez', 'Auditor'],
				['Chanel Jeraldine Fernandez', 'Public Information Officer'],
				['Robert John Garcia', 'Business Manager'],
				['Jhan Mino Daracan', 'Social Media Manager'],
				['Febbie Ann Escoto', 'Multimedia (Creative)'],
				['Sophia Cassandra Pare', 'Multimedia (Documentation)'],
				['Ashley Alessandra Annunciation', 'IT Representative I'],
				['Izhar Henjie Allague', 'IT Representative II'],
			];
		@endphp

		<div class="officers-grid">
			@foreach ($officers as [$name, $role])
				<article class="officer-card">
					<img class="officer-photo"
						 src="https://placehold.co/320x320/F7FBFC/17324D?text=Photo"
						 alt="Placeholder portrait for {{ $name }}">
					<h3>{{ $name }}</h3>
					<p>{{ $role }}</p>
				</article>
			@endforeach
		</div>
	</section>

	<section class="leadership-section" aria-labelledby="leadership-heading">
		<h2 id="leadership-heading" class="about-section-title">Leadership History</h2>

		<div class="leadership-list">
			@foreach (['2025-2026', '2024-2025', '2023-2024'] as $year)
				<details class="leadership-item">
					<summary>
						<span>SSITE Officers A.Y. {{ $year }}</span>
						<svg class="leadership-chevron" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
						</svg>
					</summary>
					<p>Officer history for A.Y. {{ $year }} will be added here.</p>
				</details>
			@endforeach
		</div>
	</section>
</div>
@endsection
