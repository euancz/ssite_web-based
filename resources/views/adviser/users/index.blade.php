@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Manage Users" subtitle="View accounts and assign student or officer roles." />

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
        <form method="GET" action="{{ route('adviser.users.index') }}" class="dashboard-filter-form">
            @csrf
            <div class="dashboard-form-field">
                <label for="users-search">Search users</label>
                <input id="users-search" type="search" name="search" value="{{ request('search') }}"
                       maxlength="100" placeholder="Name, email, student number, institute, program, year">
            </div>
            <div class="dashboard-form-field">
                <label for="users-role-filter">Filter by role</label>
                <select id="users-role-filter" name="role">
                    <option value="">All roles</option>
                    @foreach (['student', 'officer', 'adviser'] as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="dashboard-button">Search</button>
        </form>

        <x-ui.data-table label="Users">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Student number</th>
                        <th scope="col">Institute</th>
                        <th scope="col">Program</th>
                        <th scope="col">Year level</th>
                        <th scope="col">Current role</th>
                        <th scope="col">Joined</th>
                        <th scope="col">Change role</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- User rows include academic information so advisers can identify the correct account. --}}
                    @forelse ($users as $user)
                        <tr>
                            {{-- Keep the existing user link and identify each account with its shared avatar. --}}
                            <td><a href="{{ route('adviser.users.show', $user) }}" class="adviser-user-link"><x-ui.avatar :user="$user" size="sm" />{{ $user->name }}</a></td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->student_number ?? '-' }}</td>
                            <td>{{ $user->institute ?? '-' }}</td>
                            <td>{{ $user->program ?? '-' }}</td>
                            <td>{{ $user->year_level ?? '-' }}</td>
                            <td><x-ui.status-badge :status="$user->role" /></td>
                            <td>{{ $user->created_at?->format('M j, Y') ?? '-' }}</td>
                            <td>
                                {{-- SECURITY: Advisers cannot change their own role or promote/demote another adviser. --}}
                                @if (auth()->user()->is($user) || $user->isAdviser())
                                    <span class="dashboard-muted">
                                        {{ auth()->user()->is($user) ? 'Your role cannot be changed' : 'Adviser role is protected' }}
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('adviser.users.update-role', $user) }}"
                                          class="dashboard-role-form">
                                        @csrf
                                        @method('PUT')
                                        <label class="visually-hidden" for="role-{{ $user->getKey() }}">
                                            Role for {{ $user->name }}
                                        </label>
                                        <select id="role-{{ $user->getKey() }}" name="role"
                                                data-position-id="position-{{ $user->getKey() }}" required>
                                            @foreach (['student', 'officer'] as $role)
                                                <option value="{{ $role }}" @selected($user->role === $role)>
                                                    {{ ucfirst($role) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label class="visually-hidden" for="position-{{ $user->getKey() }}">
                                            Officer position for {{ $user->name }}
                                        </label>
                                        <input id="position-{{ $user->getKey() }}" type="text" name="officer_position"
                                               value="{{ $user->officer_position }}" maxlength="100"
                                               placeholder="Officer position" autocomplete="off">
                                        <button type="submit" class="dashboard-button dashboard-button-small">Save</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="dashboard-empty-state">No users match these filters.</td>
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
            const syncPositionRequirement = () => {
                if (positionInput) {
                    positionInput.required = roleSelect.value === 'officer';
                }
            };

            roleSelect.addEventListener('change', syncPositionRequirement);
            syncPositionRequirement();
        });
    </script>
@endpush
