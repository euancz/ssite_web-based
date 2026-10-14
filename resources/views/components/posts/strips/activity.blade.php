@props(['activity'])

@if ($activity->activity_date || $activity->location)
    <dl class="activity-post-details" aria-label="Activity details">
        @if ($activity->activity_date)
            <div class="activity-post-detail">
                <dt>Date</dt>
                <dd><time datetime="{{ $activity->activity_date->format('Y-m-d') }}">{{ $activity->activity_date->format('F j, Y') }}</time></dd>
            </div>
        @endif
        @if ($activity->location)
            <div class="activity-post-detail">
                <dt>Location</dt>
                <dd>{{ $activity->location }}</dd>
            </div>
        @endif
    </dl>
@endif
