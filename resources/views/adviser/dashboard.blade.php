@extends(request()->header('X-SSITE-Page-Data') === '1' ? 'layouts.page-data' : 'layouts.app')

@section('title', auth()->user()->isAdmin() ? 'Admin Dashboard' : 'Adviser Dashboard')

@section('content')
@php($pending = $pageDataPending ?? false)
<div class="dashboard-page workspace-governance-dashboard">
    @if (auth()->user()->isAdviser())
        <x-workspace.welcome title="Organization overview" description="Manage accounts and check pending submissions." :action-url="route('adviser.users.index')" action-label="Manage accounts" />

        <nav class="site-edit-shortcuts" aria-label="Website editing">
            <a class="site-edit-shortcut" href="{{ route('adviser.site.landing') }}"><x-icon name="pencil" size="sm" /> Edit landing page</a>
            <a class="site-edit-shortcut" href="{{ route('home') }}"><x-icon name="pencil" size="sm" /> Edit community website</a>
        </nav>

        <section class="workspace-governance-summary" aria-labelledby="governance-summary-title">
            <div class="workspace-governance-section-heading"><div><h2 id="governance-summary-title">People and publishing</h2></div></div>
            <ul class="workspace-governance-counts">
                <li><div class="workspace-governance-count-card"><span class="workspace-metric-icon"><x-workspace.icon name="clock" /></span><span class="workspace-metric-label">Pending submissions</span><x-ui.deferred-count name="pending" :value="$pendingTotal ?? null" :pending="$pending" /><small>Counts only; post details are restricted to the President.</small></div></li>
                <li><a class="workspace-governance-count-card" href="{{ route('adviser.users.index', ['role' => 'officer']) }}"><span class="workspace-metric-icon"><x-workspace.icon name="users" /></span><span class="workspace-metric-label">Officers</span><x-ui.deferred-count name="officers" :value="$totalOfficers ?? null" :pending="$pending" /></a></li>
                <li><a class="workspace-governance-count-card" href="{{ route('adviser.users.index', ['role' => 'student']) }}"><span class="workspace-metric-icon"><x-workspace.icon name="users" /></span><span class="workspace-metric-label">Students</span><x-ui.deferred-count name="students" :value="$totalStudents ?? null" :pending="$pending" /></a></li>
            </ul>
        </section>


        <div class="page-data-contents" data-page-data="president-alert" @if ($pending) aria-busy="true" @endif>
        @if (! $pending && ! $presidentExists)
            <p class="dashboard-error workspace-governance-alert" role="alert">No President is assigned. Pending submissions cannot be reviewed, and no fallback approver is configured.</p>
        @endif
        </div>

    @else
        <x-workspace.welcome title="Adviser access" description="Manage adviser accounts and access." :action-url="route('adviser.users.index')" action-label="Manage adviser access" />


        <section class="workspace-governance-list" aria-labelledby="current-advisers-title">
            <div class="workspace-governance-section-heading"><div><h2 id="current-advisers-title">Current advisers</h2><p>Advisers can assign student and officer roles.</p></div>
                <a class="workspace-governance-manage-link" href="{{ route('adviser.users.index') }}">Manage adviser access <x-workspace.icon name="chevron" /></a>
            </div>
            <x-ui.data-table label="Current advisers">
                <table class="dashboard-table workspace-governance-table">
                    <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Account</th></tr></thead>
                    <tbody data-page-data="advisers" @if ($pending) aria-busy="true" @endif>
                        @if ($pending)
                            <x-ui.data-skeleton :columns="3" :rows="2" />
                        @else
                        @forelse ($advisers as $adviser)
                            <tr><th scope="row">{{ $adviser->name }}</th><td>{{ $adviser->email }}</td><td><a class="workspace-governance-manage-link" href="{{ route('adviser.users.show', $adviser) }}">View <x-workspace.icon name="external" /></a></td></tr>
                        @empty
                            <tr><td colspan="3" class="workspace-governance-empty">No adviser roles are currently assigned.</td></tr>
                        @endforelse
                        @endif
                    </tbody>
                </table>
            </x-ui.data-table>
        </section>

        <details class="workspace-governance-list workspace-role-history">
            <summary>Recent role changes</summary>
            <div class="page-data-contents" data-page-data="role-history" @if ($pending) aria-busy="true" @endif>
            @if ($pending || $roleHistory->isNotEmpty())
                <x-ui.data-table label="Recent role changes">
                    <table class="dashboard-table workspace-governance-table">
                        <thead><tr><th scope="col">Account</th><th scope="col">Change</th><th scope="col">Changed by</th><th scope="col">Date</th></tr></thead>
                        <tbody>
                            @if ($pending)
                                <x-ui.data-skeleton :columns="4" />
                            @else
                            @foreach ($roleHistory as $change)
                                <tr>
                                    <th scope="row">{{ $change->target_name ?? 'Former member' }}</th>
                                    <td>{{ ucfirst($change->old_role) }} to {{ ucfirst($change->new_role) }}@if ($change->new_officer_position) ({{ $change->new_officer_position }})@endif</td>
                                    <td>{{ $change->actor_name ?? 'Former member' }}</td>
                                    <td>{{ $change->created_at }}</td>
                                </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </x-ui.data-table>
            @else
                <p class="workspace-governance-empty">No role history is available.</p>
            @endif
            </div>
        </details>
    @endif
</div>
@endsection
