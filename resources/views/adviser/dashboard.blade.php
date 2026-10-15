@extends('layouts.app')

@section('title', auth()->user()->isSuperadmin() ? 'Superadmin Dashboard' : 'Adviser Dashboard')

@section('content')
<div class="dashboard-page">
    <x-ui.page-header
        :title="'Welcome, ' . auth()->user()->name"
        :subtitle="auth()->user()->isSuperadmin() ? 'Assign adviser roles and review role changes.' : 'Assign officer roles and monitor submission counts.'"
    >
        <x-slot:actions>
            <a href="{{ route('adviser.users.index') }}" class="dashboard-button">
                {{ auth()->user()->isSuperadmin() ? 'Manage Advisers' : 'Manage Users' }}
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @if (auth()->user()->isAdviser())
        <section class="dashboard-stat-grid" aria-label="Role assignment and submission counts">
            <x-ui.stat-card label="Pending submissions" :value="$pendingTotal" />
            <x-ui.stat-card label="Officers" :value="$totalOfficers" :href="route('adviser.users.index', ['role' => 'officer'])" />
            <x-ui.stat-card label="Students" :value="$totalStudents" :href="route('adviser.users.index', ['role' => 'student'])" />
        </section>

        @if (! $presidentExists)
            <p class="dashboard-error" role="alert">No President is assigned. Pending submissions cannot be reviewed and no fallback approver is configured.</p>
        @endif

        <section class="dashboard-panel" aria-labelledby="pending-counts-heading">
            <div class="dashboard-section-heading"><div><h2 id="pending-counts-heading">Pending submission counts</h2><p>Counts only; post titles and private content are available to the President.</p></div></div>
            <div class="dashboard-stat-grid">
                @foreach ($pendingByModule as $module)
                    <x-ui.stat-card :label="$module['label']" :value="$module['count']" />
                @endforeach
            </div>
        </section>
    @else
        <section class="dashboard-panel" aria-labelledby="adviser-assignments-heading">
            <div class="dashboard-section-heading"><div><h2 id="adviser-assignments-heading">Current advisers</h2><p>Role history is available when the optional role_changes table is configured.</p></div></div>
            @forelse ($advisers as $adviser)
                <p>{{ $adviser->name }} <span class="dashboard-muted">{{ $adviser->email }}</span></p>
            @empty
                <p class="dashboard-empty-state">No adviser roles are currently assigned.</p>
            @endforelse
        </section>

        @if ($roleHistory->isNotEmpty())
            <section class="dashboard-panel" aria-labelledby="role-history-heading">
                <div class="dashboard-section-heading"><div><h2 id="role-history-heading">Recent role changes</h2></div></div>
                <x-ui.data-table label="Recent role changes">
                    <table class="dashboard-table"><thead><tr><th>Account</th><th>Change</th><th>Changed by</th><th>Date</th></tr></thead><tbody>
                        @foreach ($roleHistory as $change)
                            <tr>
                                <td>{{ $change->target_name ?? 'Former member' }}</td>
                                <td>{{ ucfirst($change->old_role) }} to {{ ucfirst($change->new_role) }}@if ($change->new_officer_position) ({{ $change->new_officer_position }})@endif</td>
                                <td>{{ $change->actor_name ?? 'Former member' }}</td>
                                <td>{{ $change->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody></table>
                </x-ui.data-table>
            </section>
        @else
            <p class="dashboard-empty-state">No role history is available.</p>
        @endif
    @endif
</div>
@endsection
