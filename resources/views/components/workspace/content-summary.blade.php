@props(['modules'])

<x-ui.data-table label="Content by module and status">
    <table class="dashboard-table workspace-content-summary">
        <thead><tr><th scope="col">Content area</th><th scope="col">Total</th><th scope="col">Pending</th><th scope="col">Published</th><th scope="col">Rejected</th><th scope="col">Archived</th><th scope="col">Create</th></tr></thead>
        <tbody>
            @forelse ($modules as $module)
                <tr>
                    <th scope="row">{{ $module['label'] }}</th>
                    @foreach (['total' => auth()->user()->canReview() ? 'all' : 'mine', 'pending' => 'pending', 'published' => 'published', 'rejected' => 'rejected', 'archived' => 'archived'] as $status => $tab)
                        <td><a href="{{ route($module['index'], ['tab' => $tab]) }}" aria-label="{{ $module['label'] }}: {{ $module[$status] }} {{ $status }}">{{ number_format($module[$status]) }}</a></td>
                    @endforeach
                    <td><a class="dashboard-text-button" href="{{ route($module['create']) }}">Create <span class="visually-hidden">{{ $module['label'] }}</span></a></td>
                </tr>
            @empty
                <tr><td colspan="7">No content areas are assigned to your position.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-ui.data-table>
<p class="profile-field-help">Published means approved and active. Archived submissions retain their review status, so status counts can overlap.</p>
