@extends('layouts.app')

@section('title', $liquidation->title)

@section('content')
<x-posts.feed-frame variant="show" stylesheet="liquidation">
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    <x-posts.card
        :post="$liquidation"
        module="Liquidation"
        route-prefix="liquidations"
        :show-url="route('liquidations.show', $liquidation)"
        :content="$liquidation->description ?? ''"
        tab="show"
        :full="true"
    >
        <x-slot:attachment>
            <x-posts.strips.liquidation :liquidation="$liquidation">
                <x-posts.attachment
                    :post="$liquidation"
                    route-prefix="liquidations"
                    guest-can-see-list
                />
            </x-posts.strips.liquidation>
        </x-slot:attachment>

        <x-slot:footerActions>
            @can('approve', $liquidation)
                @if ($liquidation->isPending())
                    <form method="POST" action="{{ route('liquidations.approve', $liquidation) }}">
                        @csrf
                        <button type="submit" class="dashboard-button">Approve</button>
                    </form>
                    <button type="button" class="dashboard-button dashboard-button-secondary"
                            data-modal-open="liquidation-show-reject-{{ $liquidation->getKey() }}">Reject</button>
                    <x-ui.reject-modal
                        id="liquidation-show-reject-{{ $liquidation->getKey() }}"
                        :action="route('liquidations.reject', $liquidation)"
                        subject="liquidation report"
                    />
                @endif
            @endcan
        </x-slot:footerActions>
    </x-posts.card>
</x-posts.feed-frame>
@endsection
