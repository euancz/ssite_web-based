@props(['step' => 1])
<ol class="workspace-composer-steps" aria-label="Create post progress">
    @foreach (['Choose type', 'Add details', 'Review & submit'] as $label)
        <li data-composer-step="{{ $loop->iteration }}" @if ($step === $loop->iteration) aria-current="step" @endif>
            <span aria-hidden="true">{{ $loop->iteration }}</span><strong>{{ $label }}</strong>
        </li>
    @endforeach
</ol>
