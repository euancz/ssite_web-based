@props(['liquidation'])

<section class="liquidation-post-details" aria-label="Liquidation report details">
    <div class="liquidation-post-attachment">{{ $slot }}</div>
    <dl class="liquidation-post-facts">
        <div class="liquidation-post-fact liquidation-post-amount">
            <dt>Amount</dt>
            <dd>{{ $liquidation->formattedAmount() }}</dd>
        </div>
        <div class="liquidation-post-fact">
            <dt>Report date</dt>
            <dd>
                @if ($liquidation->report_date)
                    <time datetime="{{ $liquidation->report_date->format('Y-m-d') }}">{{ $liquidation->report_date->format('F j, Y') }}</time>
                @else
                    <span>Not provided</span>
                @endif
            </dd>
        </div>
    </dl>
</section>
