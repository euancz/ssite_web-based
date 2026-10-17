@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
<div class="dashboard-page workspace-user-manager">
    {{-- Adviser and superadmin role assignments use actor-specific server authorization. --}}
    <x-ui.page-header title="{{ auth()->user()->isSuperadmin() ? 'Manage advisers' : 'Manage users' }}" subtitle="{{ auth()->user()->isSuperadmin() ? 'Assign or revoke adviser access for eligible accounts.' : 'Assign student and officer roles and configured positions.' }}" />

    {{-- Report the result of a completed role update without hiding validation errors. --}}
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif

    {{-- FormRequest/role validation failures are shown before the adviser retries. --}}
    @if ($errors->any())
        <div class="dashboard-error" role="alert">
            <p>Please correct the following:</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="dashboard-panel">
        <form method="GET" action="{{ route('adviser.users.index') }}" class="dashboard-filter-form workspace-user-filters">
            @csrf
            <div class="dashboard-form-field">
                <label for="users-search">Search accounts</label>
                <input id="users-search" type="search" name="search" value="{{ request('search') }}"
                       maxlength="100" placeholder="Name, email, student number, institute, program, year">
            </div>
            <div class="dashboard-form-field"><label for="users-role-filter">Role</label>
                <select id="users-role-filter" name="role"><option value="">All eligible roles</option>
                    @foreach ($targetRoles as $role)<option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="dashboard-button"><x-workspace.icon name="search" />Search</button>
        </form>

        <p class="workspace-user-results">{{ $users->total() }} {{ \Illuminate\Support\Str::plural('account', $users->total()) }} · Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</p>

        <x-ui.data-table label="Users">
            <table class="dashboard-table adviser-users-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Program</th>
                        <th scope="col">Role</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- User rows include academic information so advisers can identify the correct account. --}}
                    @forelse ($users as $user)
                        @php
                            $actor = auth()->user();
                            $assignableRoles = $actor->canAssignOfficers()
                                ? [\App\Models\User::ROLE_STUDENT, \App\Models\User::ROLE_OFFICER]
                                : ($user->isAdviser() ? [\App\Models\User::ROLE_STUDENT] : [\App\Models\User::ROLE_ADVISER]);
                        @endphp
                        <tr>
                            {{-- Keep the existing user link and identify each account with its shared avatar. --}}
                            <td><a href="{{ route('adviser.users.show', $user) }}" class="adviser-user-link"><x-ui.avatar :user="$user" size="sm" />{{ $user->name }}</a></td>
                            <td>{{ $user->program ?? '-' }}</td>
                            <td><x-ui.status-badge :status="$user->role" />
                                @if ($user->isOfficer() && ! $user->position())
                                    <p class="dashboard-error" role="status">Unrecognized position. Assign a configured title to restore posting access.</p>
                                @endif
                            </td>
                            <td>
                                {{-- SECURITY: Advisers cannot change their own role or promote/demote another adviser. --}}
                                @if (auth()->user()->is($user) || $user->isSuperadmin())
                                    <span class="dashboard-muted">{{ auth()->user()->is($user) ? 'Your role is protected' : 'Superadmin role is protected' }}</span>
                                @else
                                    <button type="button" class="dashboard-text-button workspace-role-change" data-role-dialog-open="role-dialog-{{ $user->user_id }}">Change role</button>
                                    <dialog id="role-dialog-{{ $user->user_id }}" class="reject-modal" aria-labelledby="role-title-{{ $user->user_id }}">
                                        <div class="reject-modal-content"><h2 id="role-title-{{ $user->user_id }}">Change role for {{ $user->name }}</h2>
                                            <form method="POST" action="{{ route('adviser.users.update-role', $user) }}" class="dashboard-role-form">
                                                @csrf @method('PUT')
                                                <label for="role-{{ $user->user_id }}">Role</label>
                                                <select id="role-{{ $user->user_id }}" name="role" data-position-id="position-{{ $user->user_id }}" required>
                                                    @foreach ($assignableRoles as $role)
                                                        <option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>
                                                    @endforeach
                                                </select>
                                                <div data-position-field="position-{{ $user->user_id }}" @if ($user->role !== 'officer') hidden @endif>
                                                    <label for="position-{{ $user->user_id }}">Officer position</label>
                                                    <select id="position-{{ $user->user_id }}" name="officer_position">
                                                        <option value="">Choose position</option>
                                                        @foreach ($positions as $position)
                                                            <option value="{{ $position }}" @selected($user->position() === $position)>{{ $position }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-role-dialog-close>Cancel</button><button type="submit" class="dashboard-button">Save role</button></div>
                                            </form>
                                        </div>
                                    </dialog>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="dashboard-empty-state">No users match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.data-table>

        <nav class="dashboard-pagination" aria-label="User pagination">
            <span>
                Showing {{ $users->firstItem() ?? 0 }}&ndash;{{ $users->lastItem() ?? 0 }}
                of {{ $users->total() }} users
            </span>
            <div>
                @if ($users->previousPageUrl())
                    <a href="{{ $users->previousPageUrl() }}" class="dashboard-button dashboard-button-secondary">
                        Previous
                    </a>
                @endif
                @if ($users->nextPageUrl())
                    <a href="{{ $users->nextPageUrl() }}" class="dashboard-button dashboard-button-secondary">
                        Next
                    </a>
                @endif
            </div>
        </nav>
    </section>
</div>
@endsection

@push('scripts')
    <script>
        // Match the position field's browser-required state to the selected role; server validation remains authoritative.
        document.querySelectorAll('[data-position-id]').forEach((roleSelect) => {
            const positionInput = document.getElementById(roleSelect.dataset.positionId);
            const positionField = document.querySelector(`[data-position-field="${roleSelect.dataset.positionId}"]`);
            const syncPositionRequirement = () => {
                if (positionInput) {
                    positionInput.required = roleSelect.value === 'officer';
                    positionField.hidden = roleSelect.value !== 'officer';
                }
            };

            roleSelect.addEventListener('change', syncPositionRequirement);
            syncPositionRequirement();
        });
        document.querySelectorAll('[data-role-dialog-open]').forEach((button) => button.addEventListener('click', () => document.getElementById(button.dataset.roleDialogOpen)?.showModal()));
        document.querySelectorAll('[data-role-dialog-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog')?.close()));
    </script>
@endpush
