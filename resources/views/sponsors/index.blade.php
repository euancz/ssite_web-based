@extends('layouts.app')
@section('title', 'Manage Sponsors')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Manage Sponsors" subtitle="Maintain the partners and sponsors shown on the homepage.">
        <x-slot:actions><a class="dashboard-button" href="{{ route('workspace.sponsors.create') }}"><x-workspace.icon name="plus" />Add sponsor</a></x-slot:actions>
    </x-ui.page-header>
    @if (session('status'))<p class="dashboard-success" role="status" data-flash-toast>{{ session('status') }}</p>@endif
    @php($activeFilters = (int) ($filters['visibility'] !== '') + (int) ($filters['sort'] !== 'display'))
    <form class="workspace-list-toolbar" method="GET" action="{{ route('workspace.sponsors.index') }}" aria-label="Filter sponsors">
        <x-workspace.ui.list-search id="sponsor-search" label="Find sponsors" :value="$filters['q']" placeholder="Name or description" />
        <div class="workspace-list-filter-actions">
            @if ($activeFilters || $filters['q'] !== '')
                <a class="dashboard-text-button" href="{{ route('workspace.sponsors.index') }}">Clear filters</a>
            @endif
            <x-posts.filter-panel :active-count="$activeFilters">
                <div class="site-filter-field">
                    <label for="sponsor-visibility">Visibility</label>
                    <select id="sponsor-visibility" name="visibility">
                        @foreach (['' => 'All sponsors', 'visible' => 'Visible', 'hidden' => 'Hidden'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['visibility'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="site-filter-field">
                    <label for="sponsor-sort">Sort by</label>
                    <select id="sponsor-sort" name="sort">
                        @foreach (['display' => 'Homepage display order', 'name' => 'Name (A to Z)', 'recent' => 'Newest added'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </x-posts.filter-panel>
        </div>
    </form>
    <section class="dashboard-panel">
        <x-ui.data-table label="Sponsors">
            <table class="dashboard-table"><thead><tr><th>Logo</th><th>Name</th><th>Link</th><th>Visibility</th><th>Order</th><th>Actions</th></tr></thead><tbody>
            @forelse ($sponsors as $sponsor)
                <tr>
                    <td>@if ($sponsor->logoUrl())<img src="{{ $sponsor->logoUrl() }}" alt="" width="52" height="52" style="object-fit:contain">@else No logo @endif</td>
                    <td>{{ $sponsor->name }}</td>
                    <td>
                        @if ($sponsor->website_url)
                            <x-sponsor-link :url="$sponsor->website_url" :sponsor-name="$sponsor->name" />
                        @else
                            No link
                        @endif
                    </td>
                    <td>{{ $sponsor->is_active ? 'Visible' : 'Hidden' }}</td><td>{{ $sponsor->sort_order }}</td>
                    <td>
                        <x-ui.table-actions id="sponsor-actions-{{ $sponsor->id }}" label="Actions for {{ $sponsor->name }}">
                            <a href="{{ route('workspace.sponsors.edit', $sponsor) }}">Edit</a>
                            <button type="button" onclick="document.getElementById('delete-sponsor-{{ $sponsor->id }}').showModal()">Delete</button>
                        </x-ui.table-actions>
                        <dialog id="delete-sponsor-{{ $sponsor->id }}" class="reject-modal"><div class="reject-modal-content"><h2>Delete {{ $sponsor->name }}?</h2><p>This removes the sponsor and its logo. You can hide a sponsor by editing its visibility instead.</p><div class="reject-modal-actions">
                            <button type="button" class="dashboard-button dashboard-button-secondary" onclick="this.closest('dialog').close()">Cancel</button>
                            <form method="POST" action="{{ route('workspace.sponsors.destroy', $sponsor) }}">@csrf @method('DELETE')<button type="submit" class="dashboard-button">Delete sponsor</button></form>
                        </div></div></dialog>
                    </td>
                </tr>
            @empty <tr><td colspan="6">{{ $filters['q'] !== '' || $filters['visibility'] !== '' ? 'No sponsors match these filters.' : 'No sponsors yet. Add a sponsor to display it on the homepage.' }}</td></tr> @endforelse
            </tbody></table>
        </x-ui.data-table>
        {{ $sponsors->links('vendor.pagination.ssite') }}
    </section>
</div>
@endsection
