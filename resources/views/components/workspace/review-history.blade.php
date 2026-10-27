@props(['post'])

@can('update', $post)
    @if (app(\App\Services\ContentReviewHistory::class)->available())
        @php($events = app(\App\Services\ContentReviewHistory::class)->recentFor($post, auth()->user()))
        <details class="workspace-review-history">
            <summary>Review history</summary>
            <p class="profile-field-help">Latest 20 recorded decisions, newest first. History starts when tracking is enabled; earlier decisions are not reconstructed.</p>
            @forelse ($events as $event)
                <div class="workspace-review-history-entry">
                    <p><strong>{{ ucfirst($event->decision) }}</strong> by {{ $event->reviewer_name }}
                        <time datetime="{{ \Illuminate\Support\Carbon::parse($event->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($event->created_at)->format('M j, Y g:i A') }}</time>
                    </p>
                    <p>Title at review: {{ $event->post_title }}</p>
                    @if ($event->reason)<p><strong>Reason:</strong> {{ $event->reason }}</p>@endif
                </div>
            @empty
                <p>No decisions have been recorded in this history yet.</p>
            @endforelse
            @foreach (\App\Support\ContentModules::all() as $key => $module)
                @if ($post::class === $module['model'])
                    <a class="dashboard-text-button" href="{{ route('workspace.review-history.index', ['module' => $key, 'post' => $post->getKey()]) }}">Browse all decisions for this post</a>
                @endif
            @endforeach
        </details>
    @endif
@endcan
