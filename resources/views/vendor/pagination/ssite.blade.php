<div class="ssite-pagination" data-pagination-page="{{ $paginator->currentPage() }}" data-pagination-pages="{{ $paginator->lastPage() }}">
    <p class="ssite-pagination-summary">
        @if ($paginator->total() > 0)
            Showing <strong>{{ number_format($paginator->firstItem() ?? 0) }}&ndash;{{ number_format($paginator->lastItem() ?? 0) }}</strong>
            of <strong>{{ number_format($paginator->total()) }}</strong> {{ $paginator->total() === 1 ? 'result' : 'results' }}
        @else
            No results
        @endif
    </p>
    @if ($paginator->hasPages())
        <nav class="ssite-pagination-controls" aria-label="Results pagination">
            @if ($paginator->onFirstPage())
                <span class="ssite-page-button ssite-page-direction is-disabled" aria-disabled="true"><span aria-hidden="true">&larr;</span> Previous</span>
            @else
                <a class="ssite-page-button ssite-page-direction" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"><span aria-hidden="true">&larr;</span> Previous</a>
            @endif
            <span class="ssite-pagination-position">Page <strong>{{ number_format($paginator->currentPage()) }}</strong> of {{ number_format($paginator->lastPage()) }}</span>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="ssite-page-ellipsis" aria-hidden="true">&hellip;</span>
                @elseif (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="ssite-page-button ssite-page-number is-current" aria-current="page" aria-label="Page {{ $page }}, current page">{{ $page }}</span>
                        @else
                            <a class="ssite-page-button ssite-page-number" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a class="ssite-page-button ssite-page-direction" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">Next <span aria-hidden="true">&rarr;</span></a>
            @else
                <span class="ssite-page-button ssite-page-direction is-disabled" aria-disabled="true">Next <span aria-hidden="true">&rarr;</span></span>
            @endif
        </nav>
    @endif
</div>