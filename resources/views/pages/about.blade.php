@extends('layouts.app')

@section('title', 'About Us')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/about.css') }}">
@endpush

@section('content')
<div class="about-page">
	@if (session('status')) <p class="dashboard-success" role="status">{{ session('status') }}</p> @endif
	<h1 class="about-page-title">Get to Know SSITE</h1>
	<p class="about-page-intro">A student-led community built around technology, service, and meaningful creative work.</p>

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
			{{-- SECURITY: Adviser and superadmin roles do not grant About roster editing. --}}
			@can('manage-officers')
				<details class="about-manage-menu"><summary aria-label="Manage officers"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06-1.5 2.6-.08-.02a1.65 1.65 0 0 0-1.78.76l-.04.07h-3l-.02-.08a1.65 1.65 0 0 0-1.4-1.14 1.65 1.65 0 0 0-1.73.92l-.04.08-2.84-1.02.02-.08a1.65 1.65 0 0 0-.76-1.78l-.07-.04v-3l.08-.02a1.65 1.65 0 0 0 1.14-1.4 1.65 1.65 0 0 0-.92-1.73l-.08-.04 1.02-2.84.08.02a1.65 1.65 0 0 0 1.78-.76l.04-.07h3l.02.08a1.65 1.65 0 0 0 1.4 1.14 1.65 1.65 0 0 0 1.73-.92l.04-.08 2.84 1.02-.02.08a1.65 1.65 0 0 0 .76 1.78l.07.04v3l-.08.02a1.65 1.65 0 0 0-.72 1.58z"/></svg></summary>
					<div class="about-manage-dropdown">
						<a href="{{ route('adviser.officers.create', ['academic_year' => $current, 'from' => 'about']) }}">Add officer</a>
						<button type="button" data-about-edit-toggle aria-pressed="false">Edit officers</button>
						@if ($officers->isEmpty() && $history->isNotEmpty())
							@php([$currentStart] = array_map('intval', explode('-', $current)))
							@php($previousYear = ($currentStart - 1) . '-' . $currentStart)
							@if ($history->has($previousYear))
								<button type="button" data-copy-confirm-open="copy-current-year">Copy last year's officers</button>
								<dialog id="copy-current-year" class="reject-modal"><div class="reject-modal-content"><h2>Copy last year's officers?</h2><p>Existing matching officers will be skipped.</p><div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-dialog-close>Cancel</button><form method="POST" action="{{ route('adviser.officers.copy-previous') }}">@csrf<input type="hidden" name="academic_year" value="{{ $current }}"><input type="hidden" name="from" value="about"><button type="submit" class="dashboard-button">Copy officers</button></form></div></div></dialog>
							@endif
						@endif
						<a href="{{ route('adviser.officers.index', ['academic_year' => $current]) }}">Open full manager</a>
					</div>
				</details>
			@endcan
		</div>
		{{-- Year-specific snapshots keep the public list stable when user profiles change. --}}
		<div class="officers-grid">
			@forelse ($officers as $officer)
				<article class="officer-card card-hover" tabindex="0">
					@if ($officer->photoUrl())
						{{-- The fixed frame clips photo zoom while preserving the officer grid dimensions. --}}
						<div class="officer-photo-frame card-media"><img class="officer-photo" src="{{ $officer->photoUrl() }}" alt="Officer portrait for {{ $officer->name }}"></div>
					@else
						<div class="officer-photo officer-photo-placeholder" role="img" aria-label="Photo unavailable">Photo</div>
					@endif
					<h3>{{ $officer->name }}</h3>
					<p>{{ $officer->position }}</p>
					{{-- SECURITY: Roster controls stay absent because officer assignments use the users manager. --}}
					@can('manage-officers')
						<div class="about-officer-controls" data-about-edit-controls hidden>
							<a href="{{ route('adviser.officers.edit', ['officer' => $officer, 'from' => 'about']) }}" aria-label="Edit {{ $officer->name }}"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16 4 4 4L8 20H4v-4L16 4zM14 6l4 4"/></svg></a>
							<button type="button" aria-label="Delete {{ $officer->name }}" data-delete-confirm-open="delete-officer-{{ $officer->term_id }}"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg></button>
						</div>
						<dialog id="delete-officer-{{ $officer->term_id }}" class="reject-modal">
							<div class="reject-modal-content"><h2>Delete officer?</h2><p>This removes this year's officer snapshot.</p>
								<div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-dialog-close>Cancel</button>
									<form method="POST" action="{{ route('adviser.officers.destroy', $officer) }}">@csrf @method('DELETE')<input type="hidden" name="from" value="about"><button class="dashboard-button" type="submit">Delete</button></form>
								</div>
							</div>
						</dialog>
					@endcan
				</article>
			@empty
				<div class="about-officers-empty"><p>Officers for A.Y. {{ $current }} will be announced soon.</p>
					@can('manage-officers')<a class="dashboard-button" href="{{ route('adviser.officers.create', ['academic_year' => $current, 'from' => 'about']) }}">Add officer</a>@endcan
				</div>
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
					@can('manage-officers')
						<details class="about-manage-menu about-history-menu"><summary aria-label="Manage officers for A.Y. {{ $year }}"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06-1.5 2.6-.08-.02a1.65 1.65 0 0 0-1.78.76l-.04.07h-3l-.02-.08a1.65 1.65 0 0 0-1.4-1.14 1.65 1.65 0 0 0-1.73.92l-.04.08-2.84-1.02.02-.08a1.65 1.65 0 0 0-.76-1.78l-.07-.04v-3l.08-.02a1.65 1.65 0 0 0 1.14-1.4 1.65 1.65 0 0 0-.92-1.73l-.08-.04 1.02-2.84.08.02a1.65 1.65 0 0 0 1.78-.76l.04-.07h3l.02.08a1.65 1.65 0 0 0 1.4 1.14 1.65 1.65 0 0 0 1.73-.92l.04-.08 2.84 1.02-.02.08a1.65 1.65 0 0 0 .76 1.78l.07.04v3l-.08.02a1.65 1.65 0 0 0-.72 1.58z"/></svg></summary>
							<div class="about-manage-dropdown">
								<a href="{{ route('adviser.officers.create', ['academic_year' => $year, 'from' => 'about']) }}">Add officer to this year</a>
								<button type="button" data-about-edit-toggle aria-pressed="false">Edit officers</button>
								<a href="{{ route('adviser.officers.index', ['academic_year' => $year]) }}">Open full manager</a>
							</div>
						</details>
					@endcan
					<div class="officers-grid">
						@foreach ($yearOfficers as $officer)
							<article class="officer-card card-hover" tabindex="0">
								@if ($officer->photoUrl())
									{{-- The fixed frame clips photo zoom while preserving the officer grid dimensions. --}}
									<div class="officer-photo-frame card-media"><img class="officer-photo" src="{{ $officer->photoUrl() }}" alt="Officer portrait for {{ $officer->name }}"></div>
								@else
									<div class="officer-photo officer-photo-placeholder" role="img" aria-label="Photo unavailable">Photo</div>
								@endif
								<h3>{{ $officer->name }}</h3><p>{{ $officer->position }}</p>
								@can('manage-officers')
									<div class="about-officer-controls" data-about-edit-controls hidden>
										<a href="{{ route('adviser.officers.edit', ['officer' => $officer, 'from' => 'about']) }}" aria-label="Edit {{ $officer->name }}"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16 4 4 4L8 20H4v-4L16 4zM14 6l4 4"/></svg></a>
										<button type="button" aria-label="Delete {{ $officer->name }}" data-delete-confirm-open="delete-officer-{{ $officer->term_id }}"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg></button>
									</div>
									<dialog id="delete-officer-{{ $officer->term_id }}" class="reject-modal"><div class="reject-modal-content"><h2>Delete officer?</h2><p>This removes this year's officer snapshot.</p><div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-dialog-close>Cancel</button><form method="POST" action="{{ route('adviser.officers.destroy', $officer) }}">@csrf @method('DELETE')<input type="hidden" name="from" value="about"><button class="dashboard-button" type="submit">Delete</button></form></div></div></dialog>
								@endcan
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

@can('manage-officers')
@push('scripts')
<script>
    // Edit mode is page-local; disabling it hides all card controls without changing the public card grid.
    document.querySelectorAll('[data-about-edit-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const enabled = toggle.getAttribute('aria-pressed') !== 'true';
            document.querySelectorAll('[data-about-edit-toggle]').forEach((button) => button.setAttribute('aria-pressed', String(enabled)));
            document.querySelectorAll('[data-about-edit-controls]').forEach((controls) => { controls.hidden = !enabled; });
        });
    });
    document.querySelectorAll('[data-delete-confirm-open], [data-copy-confirm-open]').forEach((button) => {
        button.addEventListener('click', () => document.getElementById(button.dataset.deleteConfirmOpen || button.dataset.copyConfirmOpen)?.showModal());
    });
    document.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog')?.close()));
    document.querySelectorAll('.about-manage-menu').forEach((menu) => menu.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') { menu.open = false; menu.querySelector('summary')?.focus(); }
    }));
    document.addEventListener('click', (event) => document.querySelectorAll('.about-manage-menu').forEach((menu) => { if (!menu.contains(event.target)) menu.open = false; }));
</script>
@endpush
@endcan
