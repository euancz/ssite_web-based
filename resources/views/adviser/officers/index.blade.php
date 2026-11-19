@extends('layouts.app')

@section('title', 'Manage Officers')

@section('content')
<div class="dashboard-page president-officers-page">
    <x-ui.page-header title="Manage Officers" subtitle="Maintain the annual officer snapshots shown on About." />
    @if (session('status')) <p class="dashboard-success" role="status" data-flash-toast>{{ session('status') }}</p> @endif
    @if ($errors->any()) <div class="dashboard-error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif
    <section class="dashboard-panel">
        <form method="GET" action="{{ route('adviser.officers.index') }}" class="dashboard-filter-form">
            <div class="dashboard-form-field">
                <label for="officer-year">Academic year</label>
                <select id="officer-year" name="academic_year" onchange="this.form.requestSubmit()">
                    @foreach ($years as $option)
                        <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <a class="dashboard-button" href="{{ route('adviser.officers.create', ['academic_year' => $year]) }}"><x-workspace.icon name="plus" />Add officer</a>
        </form>
        <p class="workspace-review-list-summary">{{ $officers->count() }} {{ \Illuminate\Support\Str::plural('officer', $officers->count()) }} recorded for A.Y. {{ $year }}</p>
        <x-ui.data-table label="Officers for {{ $year }}">
            <table class="dashboard-table officers-manager-table"><thead><tr><th>Photo</th><th>Name</th><th>Position</th><th>Order</th><th>Actions</th></tr></thead><tbody>
            @forelse ($officers as $officer)
                <tr><td>@if ($officer->photoUrl())<img src="{{ $officer->photoUrl() }}" alt="{{ $officer->name }}" width="52" height="52">@else Photo @endif</td><td>{{ $officer->name }}</td><td>{{ $officer->position }}</td><td>{{ $officer->sort_order }}</td><td>
                    <x-ui.table-actions id="officer-actions-{{ $officer->id }}" label="Actions for {{ $officer->name }}">
                        <a href="{{ route('adviser.officers.edit', $officer) }}">Edit</a>
                        <button type="button" onclick="document.getElementById('delete-officer-{{ $officer->id }}').showModal()">Delete</button>
                    </x-ui.table-actions>
                    <dialog id="delete-officer-{{ $officer->id }}" class="reject-modal"><div class="reject-modal-content"><h2>Delete officer?</h2><p>This removes this year's officer snapshot.</p>
                        <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" onclick="this.closest('dialog').close()">Cancel</button>
                            <form method="POST" action="{{ route('adviser.officers.destroy', $officer) }}">@csrf @method('DELETE')<button class="dashboard-button" type="submit">Delete</button></form>
                        </div></div></dialog>
                </td></tr>
            @empty <tr><td colspan="5">No officers are recorded for A.Y. {{ $year }}.</td></tr> @endforelse
            </tbody></table>
        </x-ui.data-table>
    </section>
</div>
@endsection
