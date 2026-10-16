@if ($paginator->hasPages())
    <nav class="ssite-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <p class="ssite-pagination-summary">
            {{ __('Showing') }}
            <strong>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</strong>
            {{ __('of') }} <strong>{{ $paginator->total() }}</strong>
        </p>

        <div class="ssite-pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="ssite-page-button is-disabled" aria-disabled="true">Previous</span>
            @else
                <a class="ssite-page-button" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="ssite-page-ellipsis" aria-hidden="true">{{ $element }}</span>
                @elseif (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="ssite-page-button is-current" aria-current="page" aria-label="{{ __('Page :page', ['page' => $page]) }}">{{ $page }}</span>
                        @else
                            <a class="ssite-page-button" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="ssite-page-button" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="ssite-page-button is-disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
