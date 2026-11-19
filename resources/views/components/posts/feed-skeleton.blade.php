@props(['stylesheet' => null, 'visible' => false])

<div class="post-feed-skeleton" data-feed-skeleton aria-hidden="true" @unless ($visible) hidden @endunless>
    @for ($card = 0; $card < 3; $card++)
        @if ($stylesheet === 'liquidation')
            <article class="liquidation-report-row">
                <div class="liquidation-report-main">
                    <div class="liquidation-report-icon"><x-workspace.icon name="file-text" /><span>PDF</span></div>
                    <div class="liquidation-report-copy skeleton-lines">
                        <span class="skeleton-block skeleton-line skeleton-line--heading"></span>
                        <span class="skeleton-block skeleton-line skeleton-line--short"></span>
                        <span class="skeleton-block skeleton-line"></span>
                    </div>
                    <div class="liquidation-report-facts">
                        <span>Report date</span><span class="skeleton-block skeleton-line"></span>
                        <span>Amount</span><span class="skeleton-block skeleton-line skeleton-line--short"></span>
                    </div>
                </div>
                <div class="liquidation-report-actions"></div>
            </article>
        @else
            <article class="post-feed-skeleton-card">
                <div class="post-feed-skeleton-author">
                    <span class="post-feed-skeleton-block post-feed-skeleton-avatar"></span>
                    <span class="post-feed-skeleton-lines">
                        <span class="post-feed-skeleton-block post-feed-skeleton-line post-feed-skeleton-name"></span>
                        <span class="post-feed-skeleton-block post-feed-skeleton-line post-feed-skeleton-meta"></span>
                    </span>
                </div>
                <span class="post-feed-skeleton-block post-feed-skeleton-line post-feed-skeleton-title"></span>
                <span class="post-feed-skeleton-block post-feed-skeleton-line post-feed-skeleton-copy"></span>
                <span class="post-feed-skeleton-block post-feed-skeleton-line"></span>
                @if (in_array($stylesheet, ['articles', 'activities', 'achievements'], true))
                    <div class="post-card-media"><span class="post-feed-skeleton-block post-feed-skeleton-media"></span></div>
                @elseif ($stylesheet === 'documents')
                    <div class="post-card-strip post-feed-attachment">
                        <div class="post-feed-pdf-mark"><x-icon name="file-text" /><span>PDF</span></div>
                        <div class="post-feed-skeleton-lines">
                            <span class="post-feed-skeleton-block post-feed-skeleton-line post-feed-skeleton-name"></span>
                            <span class="post-feed-skeleton-block post-feed-skeleton-line post-feed-skeleton-meta"></span>
                        </div>
                    </div>
                @endif
                <span class="post-feed-skeleton-block post-feed-skeleton-footer"></span>
            </article>
        @endif
    @endfor
</div>
