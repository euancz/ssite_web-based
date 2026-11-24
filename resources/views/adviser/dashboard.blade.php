@extends(request()->header('X-SSITE-Page-Data') === '1' ? 'layouts.page-data' : 'layouts.app')

@section('title', auth()->user()->isAdmin() ? 'Admin Dashboard' : 'Adviser Dashboard')

@section('content')
@php($pending = $pageDataPending ?? false)
<div class="dashboard-page workspace-dashboard">
    @if (auth()->user()->isAdviser())
        <x-workspace.welcome title="Organization overview" description="Manage accounts and check pending submissions." />

        <nav class="site-edit-shortcuts" aria-label="Website editing">
            <a class="site-edit-shortcut" href="{{ route('adviser.site.landing') }}"><x-icon name="pencil" size="sm" /> Edit landing page</a>
            <a class="site-edit-shortcut" href="{{ route('home') }}"><x-icon name="pencil" size="sm" /> Edit community website</a>
        </nav>

        <x-workspace.dashboard-summary class="workspace-dashboard-summary--three" label="People and publishing" :pending="$pending" :metrics="[
            'pending' => [
                'label' => 'Pending submissions', 'value' => $pendingTotal ?? null, 'icon' => 'clock',
                'hint' => 'The President reviews submissions.',
            ],
            'officers' => [
                'label' => 'Officers', 'value' => $totalOfficers ?? null, 'icon' => 'users',
                'url' => route('adviser.users.index', ['role' => 'officer']),
            ],
            'students' => [
                'label' => 'Students', 'value' => $totalStudents ?? null, 'icon' => 'users',
                'url' => route('adviser.users.index', ['role' => 'student']),
            ],
        ]" />

        <div class="page-data-contents" data-page-data="president-alert" @if ($pending) aria-busy="true" @endif>
        @if (! $pending && ! $presidentExists)
            <p class="dashboard-error workspace-governance-alert" role="alert">No President is assigned. Pending submissions cannot be reviewed, and no fallback approver is configured.</p>
        @endif
        </div>

    @else
        <x-workspace.welcome title="Adviser access" description="Manage adviser accounts and access." />

        <x-workspace.dashboard-summary label="Adviser overview" :pending="$pending" :metrics="[
            'adviser-count' => ['label' => 'Current advisers', 'value' => $pending ? null : $advisers->count(), 'icon' => 'users'],
        ]" />

        <section class="workspace-officer-recent" aria-labelledby="current-advisers-title">
            <div class="workspace-officer-section-heading">
                <div><h2 id="current-advisers-title">Current advisers</h2><p>Advisers can assign student and officer roles.</p></div>
                <a class="dashboard-text-button" href="{{ route('adviser.users.index') }}">Manage adviser access</a>
            </div>
            <x-ui.data-table label="Current advisers">
                <table class="dashboard-table workspace-officer-table">
                    <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Account</th></tr></thead>
                    <tbody data-page-data="advisers" @if ($pending) aria-busy="true" @endif>
                        @if ($pending)
                            <x-ui.data-skeleton :columns="3" :rows="2" />
                        @else
                        @forelse ($advisers as $adviser)
                            <tr><th scope="row">{{ $adviser->name }}</th><td>{{ $adviser->email }}</td><td><a class="workspace-officer-manage-link" href="{{ route('adviser.users.show', $adviser) }}">View <x-workspace.icon name="external" /></a></td></tr>
                        @empty
                            <tr><td colspan="3" class="workspace-officer-empty">No adviser roles are currently assigned.</td></tr>
                        @endforelse
                        @endif
                    </tbody>
                </table>
            </x-ui.data-table>
        </section>
    @endif
</div>
@endsection
