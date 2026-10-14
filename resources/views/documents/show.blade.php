@extends('layouts.app')

@section('title', $document->title)

@section('content')
<x-posts.feed-frame variant="show" stylesheet="documents">
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    <x-posts.card
        :post="$document"
        module="Documents"
        route-prefix="documents"
        :show-url="route('documents.show', $document)"
        :content="$document->description ?? ''"
        tab="show"
        :full="true"
    >
        <x-slot:attachment>
            <x-posts.attachment
                :post="$document"
                route-prefix="documents"
                :category="$document->category ?: 'Uncategorized'"
                guest-can-see-list
            />
        </x-slot:attachment>

        <x-slot:footerActions>
            @can('approve', $document)
                @if ($document->isPending())
                    <form method="POST" action="{{ route('documents.approve', $document) }}">
                        @csrf
                        <button type="submit" class="dashboard-button">Approve</button>
                    </form>
                    <button type="button" class="dashboard-button dashboard-button-secondary"
                            data-modal-open="document-show-reject-{{ $document->getKey() }}">Reject</button>
                    <x-ui.reject-modal
                        id="document-show-reject-{{ $document->getKey() }}"
                        :action="route('documents.reject', $document)"
                        subject="document"
                    />
                @endif
            @endcan
        </x-slot:footerActions>
    </x-posts.card>
</x-posts.feed-frame>
@endsection
