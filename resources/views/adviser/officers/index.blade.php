@extends('layouts.app')

@section('title', 'Manage Officers')

@section('content')
<div class="dashboard-page">
    {{-- The primary add action stays in the header; yearly copy and filtering remain one click away. --}}
    <x-ui.page-header title="Manage Officers" subtitle="Maintain the annual officer snapshots shown on About.">
        <x-slot:actions><a class="dashboard-button" href="{{ route('adviser.officers.create', ['academic_year' => $year]) }}">Add officer</a></x-slot:actions>
    </x-ui.page-header>
    @if (session('status')) <p class="dashboard-success" role="status">{{ session('status') }}</p> @endif
    @if ($errors->any()) <div class="dashboard-error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif
    <section class="dashboard-panel">
        <form method="GET" action="{{ route('adviser.officers.index') }}" class="dashboard-filter-form">
            <div class="dashboard-form-field"><label for="officer-year">Academic year</label><select id="officer-year" name="academic_year">
                @foreach ($years as $option)<option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>@endforeach
            </select></div><button class="dashboard-button">Show year</button>
        </form>
        <details class="officer-manager-more"><summary class="dashboard-button dashboard-button-secondary">More</summary>
            <div><button type="button" data-copy-confirm-open="copy-previous-year">Copy previous year's officers</button></div>
        </details>
        <dialog id="copy-previous-year" class="reject-modal"><div class="reject-modal-content"><h2>Copy previous year's officers?</h2><p>Matching rows in this year will be skipped.</p><div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-officer-dialog-close>Cancel</button><form method="POST" action="{{ route('adviser.officers.copy-previous') }}">@csrf<input type="hidden" name="academic_year" value="{{ $year }}"><button class="dashboard-button" type="submit">Copy officers</button></form></div></div></dialog>
        <x-ui.data-table label="Officers for {{ $year }}">
            <table class="dashboard-table officers-manager-table"><thead><tr><th>Photo</th><th>Name</th><th>Position</th><th>Order</th><th>Actions</th></tr></thead><tbody>
            @forelse ($officers as $officer)
                <tr><td>@if ($officer->photoUrl())<img src="{{ $officer->photoUrl() }}" alt="{{ $officer->name }}" width="52" height="52">@else Photo @endif</td><td>{{ $officer->name }}</td><td>{{ $officer->position }}</td><td>{{ $officer->sort_order }}</td><td>
                    <a href="{{ route('adviser.officers.edit', $officer) }}">Edit</a>
                    <button type="button" class="dashboard-button dashboard-button-secondary" onclick="document.getElementById('delete-officer-{{ $officer->term_id }}').showModal()">Delete</button>
                    <dialog id="delete-officer-{{ $officer->term_id }}" class="reject-modal"><div class="reject-modal-content"><h2>Delete officer?</h2><p>This removes this year's officer snapshot.</p>
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

@push('scripts')
<script>
    document.querySelector('[data-copy-confirm-open]')?.addEventListener('click', (event) => document.getElementById(event.currentTarget.dataset.copyConfirmOpen)?.showModal());
    document.querySelectorAll('[data-officer-dialog-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog')?.close()));
</script>
@endpush
