@extends('layouts.app')

@section('title', 'User Information')

@section('content')
<div class="dashboard-page">
    <x-page-header title="User Information" subtitle="Read-only account and profile details." />

    {{-- Read-only detail view lets advisers inspect a complete account without editing user-submitted fields here. --}}
    <section class="dashboard-panel profile-readonly-grid">
        @foreach ([
            'Name' => $user->name,
            'Email' => $user->email,
            'Role' => ucfirst($user->role),
            'Officer position' => $user->officer_position ?: '-',
            'Student number' => $user->student_number ?: '-',
            'Institute' => $user->institute ?: '-',
            'Program' => $user->program ?: '-',
            'Year level' => $user->year_level ?: '-',
            'Gender' => $user->gender ?: '-',
            'Contact number' => $user->contact_number ?: '-',
            'Address' => $user->address ?: '-',
            'Joined' => $user->created_at?->format('M j, Y') ?? '-',
        ] as $label => $value)
            <div>
                <dt>{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            </div>
        @endforeach
    </section>

    <a href="{{ route('adviser.users.index') }}" class="dashboard-button dashboard-button-secondary">Back to users</a>
</div>
@endsection
