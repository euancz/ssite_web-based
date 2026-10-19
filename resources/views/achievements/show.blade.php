@extends('layouts.app')

@section('title', $achievement->title)

@section('content')
<x-posts.feed-frame variant="show" stylesheet="achievements">
    @if (session('status'))
        <p class="dashboard-success" role="status" data-flash-toast>{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert" data-flash-toast>{{ session('error') }}</p>
    @endif

    <x-posts.card
        :post="$achievement"
        module="Achievements"
        route-prefix="achievements"
        :show-url="route('achievements.show', $achievement)"
        :content="$achievement->description"
        tab="show"
        :full="true"
        :media-url="$achievement->imageUrl()"
        :media-alt="$achievement->title"
    >
        <x-slot:strip>
            <x-posts.strips.achievement :achievement="$achievement" />
        </x-slot:strip>

        <x-slot:footerActions>
            @can('approve', $achievement)
                @if ($achievement->isPending())
                    <form method="POST" action="{{ route('achievements.approve', $achievement) }}">
                        @csrf
                        <button type="submit" class="dashboard-button">Approve</button>
                    </form>
                    <button type="button" class="dashboard-button dashboard-button-secondary"
                            data-modal-open="achievement-show-reject-{{ $achievement->getKey() }}">Reject</button>
                    <x-ui.reject-modal
                        id="achievement-show-reject-{{ $achievement->getKey() }}"
                        :action="route('achievements.reject', $achievement)"
                        subject="achievement"
                    />
                @endif
            @endcan
        </x-slot:footerActions>
    </x-posts.card>
</x-posts.feed-frame>
@endsection
