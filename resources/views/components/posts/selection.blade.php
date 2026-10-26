@props(['post', 'id', 'routePrefix', 'category', 'date' => null, 'expense' => false])

<dialog id="{{ $id }}" class="document-selection {{ $expense ? 'transparency-selection' : '' }}" aria-labelledby="{{ $id }}-title">
    <button type="button" class="document-selection-close" data-selection-close aria-label="Close document"><x-icon name="x" size="sm" /></button>
    <header class="document-selection-heading">
        <h2 id="{{ $id }}-title" tabindex="-1">{{ $post->title }}</h2>
        @if ($post->description)
            <p>{{ $post->description }}</p>
        @endif
        @if ($expense)
            <p class="transparency-selection-amount">
                @if ($post->amount !== null)
                    <span class="visually-hidden">Expense: </span>&minus;{{ preg_replace('/\.00$/', '', $post->formattedAmount()) }}
                @else
                    Amount unavailable
                @endif
            </p>
        @endif
        <div class="document-selection-badges">
            <span><x-icon name="file-text" size="sm" />{{ $category }}</span>
            @if ($date)
                <span><x-icon name="calendar-days" size="sm" /><time datetime="{{ $date->toDateString() }}">{{ $date->format('F j, Y') }}</time></span>
            @endif
        </div>
    </header>
    <section class="document-selection-preview document-pdf-viewer" tabindex="0" aria-label="Document preview"
        @if (auth()->user()?->can('viewFile', $post))
            data-selection-viewer
            data-pdf-url="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute($routePrefix . '.view', now()->addMinutes(15), [$post->getRouteKeyName() === 'liquidation_id' ? 'liquidation' : 'document' => $post]) }}"
        @endif>
        <div class="document-selection-preview-heading">
            <h3>Document Preview</h3>
            @can('viewFile', $post)
                <button type="button" data-selection-expand aria-label="Expand document preview" aria-pressed="false"><x-icon name="external-link" size="sm" /></button>
            @endcan
        </div>
        @can('viewFile', $post)
            <div class="document-pdf-stage" data-pdf-stage>
                <canvas class="document-pdf-canvas" data-pdf-canvas role="img" aria-label="PDF page 1"></canvas>
            </div>
            <div class="document-selection-controls" role="group" aria-label="Document pages">
                <button type="button" data-pdf-previous disabled>Previous</button>
                <span data-pdf-page-status aria-live="polite">Loading page...</span>
                <button type="button" data-pdf-next disabled>Next</button>
            </div>
            <p data-pdf-status role="status" aria-live="polite">Loading document...</p>
            <button type="button" data-pdf-retry hidden>Reload page</button>
        @else
            <div class="document-selection-unavailable">
                <x-icon name="file-text" size="lg" />
                @guest
                    <p>Sign in to preview this document.</p>
                    <a href="{{ route($routePrefix . '.show', $post) }}">Open document details</a>
                @else
                    <p>The PDF preview is not available for this account.</p>
                @endguest
            </div>
        @endcan
    </section>
</dialog>
