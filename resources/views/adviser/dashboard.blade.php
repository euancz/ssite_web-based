@extends('layouts.app')

@section('title', auth()->user()->isSuperadmin() ? 'Superadmin Dashboard' : 'Adviser Dashboard')

@section('content')
<div class="dashboard-page workspace-governance-dashboard">
    @if (auth()->user()->isAdviser())
        <x-workspace.welcome title="Organization overview" description="Support the people behind SSITE. Manage student and officer accounts, and stay connected to the organization." :action-url="route('adviser.users.index')" action-label="Manage accounts" />

        <section class="workspace-governance-summary" aria-labelledby="governance-summary-title">
            <div class="workspace-governance-section-heading"><div><h2 id="governance-summary-title">People and publishing</h2><p>Quick counts for your organization.</p></div></div>
            <ul class="workspace-governance-counts">
                <li><div class="workspace-governance-count-card"><span class="workspace-metric-icon"><x-workspace.icon name="clock" /></span><span class="workspace-metric-label">Pending submissions</span><strong class="workspace-metric-value"><x-ui.count-up :value="$pendingTotal" /></strong><small>Counts only; post details are restricted to the President.</small></div></li>
                <li><a class="workspace-governance-count-card" href="{{ route('adviser.users.index', ['role' => 'officer']) }}"><span class="workspace-metric-icon"><x-workspace.icon name="users" /></span><span class="workspace-metric-label">Officers</span><strong class="workspace-metric-value"><x-ui.count-up :value="$totalOfficers" /></strong><small>Review officer accounts</small></a></li>
                <li><a class="workspace-governance-count-card" href="{{ route('adviser.users.index', ['role' => 'student']) }}"><span class="workspace-metric-icon"><x-workspace.icon name="users" /></span><span class="workspace-metric-label">Students</span><strong class="workspace-metric-value"><x-ui.count-up :value="$totalStudents" /></strong><small>Review student accounts</small></a></li>
            </ul>
        </section>


        @if (! $presidentExists)
            <p class="dashboard-error workspace-governance-alert" role="alert">No President is assigned. Pending submissions cannot be reviewed, and no fallback approver is configured.</p>
        @endif

    @else
        <x-workspace.welcome title="Adviser access" description="Keep your organization in good hands. Manage adviser assignments and review recent role changes." :action-url="route('adviser.users.index')" action-label="Manage adviser access" />


        <section class="workspace-governance-list" aria-labelledby="current-advisers-title">
            <div class="workspace-governance-section-heading"><div><h2 id="current-advisers-title">Current advisers</h2><p>Advisers can assign student and officer roles.</p></div>
                <a class="workspace-governance-manage-link" href="{{ route('adviser.users.index') }}">Manage adviser access <x-workspace.icon name="chevron" /></a>
            </div>
            <x-ui.data-table label="Current advisers">
                <table class="dashboard-table workspace-governance-table">
                    <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Account</th></tr></thead>
                    <tbody>
                        @forelse ($advisers as $adviser)
                            <tr><th scope="row">{{ $adviser->name }}</th><td>{{ $adviser->email }}</td><td><a class="workspace-governance-manage-link" href="{{ route('adviser.users.show', $adviser) }}">View <x-workspace.icon name="external" /></a></td></tr>
                        @empty
                            <tr><td colspan="3" class="workspace-governance-empty">No adviser roles are currently assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.data-table>
        </section>

        <section class="workspace-governance-list" aria-labelledby="role-history-heading">
            <div class="workspace-governance-section-heading"><div><h2 id="role-history-heading">Recent role changes</h2></div></div>
            @if ($roleHistory->isNotEmpty())
                <x-ui.data-table label="Recent role changes">
                    <table class="dashboard-table workspace-governance-table">
                        <thead><tr><th scope="col">Account</th><th scope="col">Change</th><th scope="col">Changed by</th><th scope="col">Date</th></tr></thead>
                        <tbody>
                            @foreach ($roleHistory as $change)
                                <tr>
                                    <th scope="row">{{ $change->target_name ?? 'Former member' }}</th>
                                    <td>{{ ucfirst($change->old_role) }} to {{ ucfirst($change->new_role) }}@if ($change->new_officer_position) ({{ $change->new_officer_position }})@endif</td>
                                    <td>{{ $change->actor_name ?? 'Former member' }}</td>
                                    <td>{{ $change->created_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-ui.data-table>
            @else
                <p class="workspace-governance-empty">No role history is available.</p>
            @endif
        </section>
    @endif
    <x-workspace.dashboard-guide />
</div>
@endsection
