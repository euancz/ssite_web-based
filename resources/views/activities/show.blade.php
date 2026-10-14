@extends('layouts.app')

@section('title', $activity->title)

@section('content')
<x-posts.feed-frame variant="show" stylesheet="activities">
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    <x-posts.card
        :post="$activity"
        module="Activities"
        route-prefix="activities"
        :show-url="route('activities.show', $activity)"
        :content="$activity->description"
        tab="show"
        :full="true"
        :media-url="$activity->imageUrl()"
        :media-alt="$activity->title"
    >
        <x-slot:strip>
            <x-posts.strips.activity :activity="$activity" />
        </x-slot:strip>

        <x-slot:footerActions>
            @can('approve', $activity)
                @if ($activity->isPending())
                    <form method="POST" action="{{ route('activities.approve', $activity) }}">
                        @csrf
                        <button type="submit" class="dashboard-button">Approve</button>
                    </form>
                    <button type="button" class="dashboard-button dashboard-button-secondary"
                            data-modal-open="activity-show-reject-{{ $activity->getKey() }}">Reject</button>
                    <x-ui.reject-modal
                        id="activity-show-reject-{{ $activity->getKey() }}"
                        :action="route('activities.reject', $activity)"
                        subject="activity"
                    />
                @endif
            @endcan
        </x-slot:footerActions>
    </x-posts.card>
</x-posts.feed-frame>
@endsection
