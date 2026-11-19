@php
	$photo = is_object($officer) && method_exists($officer, 'photoUrl')
		? $officer->photoUrl()
		: data_get($officer, 'photo_url');
	$name = data_get($officer, 'name') ?? 'Officer Name';
	$position = data_get($officer, 'position') ?? 'Officer Role';
@endphp
<article class="officer-card" @if ($reveal ?? false) data-motion="reveal" @endif>
	<div class="officer-photo-frame">
		@if ($photo)
			<img class="officer-photo" src="{{ $photo }}" alt="Portrait of {{ $name }}" loading="lazy" decoding="async">
		@else
			<span class="officer-photo-placeholder">Photo unavailable</span>
		@endif
	</div>
	<div class="officer-nameplate">
		<h3>{{ $name }}</h3>
		<p>{{ $position }}</p>
	</div>
</article>
