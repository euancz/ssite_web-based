@props(['achievement'])

@if ($achievement->awardee || $achievement->category || $achievement->achievement_date)
    <dl class="achievement-post-details" aria-label="Achievement details">
        @if ($achievement->awardee)
            <div class="achievement-post-detail achievement-post-awardee">
                <dt>Awardee</dt>
                <dd>{{ $achievement->awardee }}</dd>
            </div>
        @endif
        @if ($achievement->achievement_date)
            <div class="achievement-post-detail">
                <dt>Date</dt>
                <dd><time datetime="{{ $achievement->achievement_date->format('Y-m-d') }}">{{ $achievement->achievement_date->format('F j, Y') }}</time></dd>
            </div>
        @endif
        @if ($achievement->category)
            <div class="achievement-post-detail">
                <dt>Category</dt>
                <dd><span class="achievement-post-category">{{ $achievement->category }}</span></dd>
            </div>
        @endif
    </dl>
@endif
