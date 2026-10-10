@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    {{-- SECURITY: The controller scopes every row to the signed-in user; escaped payload text is rendered through Blade. --}}
    <section class="notification-page">
        <div class="notification-page-heading">
            <div>
                <h1>Notifications</h1>
                <p>Updates about posts and adviser review.</p>
            </div>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="notification-page-action">Mark all as read</button>
            </form>
        </div>

        <nav class="notification-filter" aria-label="Notification filters">
            <a href="{{ route('notifications.index', ['filter' => 'all']) }}" class="{{ $filter === 'all' ? 'is-active' : '' }}">All</a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'is-active' : '' }}">Unread</a>
        </nav>

        <div class="notification-page-list">
            @forelse ($notifications as $notification)
                @php($data = (array) $notification->data)
                <article class="notification-page-card {{ $notification->read_at ? '' : 'is-unread' }}">
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="notification-page-open-form">
                        @csrf
                        <button type="submit" class="notification-page-open">
                            <span class="notification-type-icon notification-type-{{ $data['type'] ?? 'published' }}" aria-hidden="true">{{ strtoupper(substr($data['type'] ?? 'P', 0, 1)) }}
                                <img class="notification-actor-avatar" src="{{ route('notifications.avatar', $notification->id) }}" alt="">
                            </span>
                            <span class="notification-page-copy">
                                <strong>{{ $data['message'] ?? 'You have a new notification.' }}</strong>
                                <span>{{ $data['title'] ?? '' }}</span>
                                <small>{{ $data['actor_name'] ?? 'SSITE' }} · {{ ucfirst($data['post_type'] ?? 'post') }} · {{ $notification->created_at?->diffForHumans() }}</small>
                                @if (! empty($data['rejection_reason']))
                                    <span class="notification-page-reason">Reason: {{ $data['rejection_reason'] }}</span>
                                @endif
                            </span>
                            @if (! $notification->read_at)<span class="notification-entry-unread-dot" aria-label="Unread"></span>@endif
                        </button>
                    </form>
                    <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="notification-dismiss" aria-label="Dismiss notification">Dismiss</button>
                    </form>
                </article>
            @empty
                <p class="notification-page-empty">You're all caught up</p>
            @endforelse
        </div>

        {{ $notifications->links() }}
    </section>
@endsection
