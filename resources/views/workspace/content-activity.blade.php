@extends('layouts.app')
@section('title', 'Content activity')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Content activity" subtitle="See when submissions were created and review decisions were recorded for posts you can currently manage." />
    <x-ui.form-errors />
    <form method="GET" action="{{ route('workspace.content-activity.index') }}" class="workspace-review-filters">
        <div class="dashboard-form-field"><label for="activity-period">Period</label>
            <select id="activity-period" name="days"><option value="7" @selected($filters['days'] === 7)>Last 7 days</option><option value="30" @selected($filters['days'] === 30)>Last 30 days</option></select>
        </div>
        <div class="dashboard-form-field"><label for="activity-module">Content area</label>
            <select id="activity-module" name="module"><option value="">All assigned areas</option>@foreach ($modules as $key => $module)<option value="{{ $key }}" @selected($filters['module'] === $key)>{{ $module['label'] }}</option>@endforeach</select>
        </div>
        <div class="profile-form-actions"><button class="dashboard-button" type="submit">Update report</button></div>
    </form>
    <p>{{ $report['start']->format('M j, Y') }}–{{ $report['end']->format('M j, Y') }}. Dates use {{ config('app.timezone') }}; today is incomplete.</p>
    <div class="content-activity-charts">
        @foreach (['submissions' => 'Submissions created', 'decisions' => 'Recorded review decisions'] as $metric => $label)
            <section class="dashboard-panel" aria-labelledby="activity-{{ $metric }}-heading">
                <h2 id="activity-{{ $metric }}-heading">{{ $label }}</h2>
                @if ($metric === 'decisions' && ! $report['historyAvailable'])
                    <p>Review tracking is not available on this installation.</p>
                @else
                    @php($total = $metric === 'submissions' ? $report['totals']['submissions'] : $report['totals']['approved'] + $report['totals']['rejected'])
                    <p><strong>{{ $total }}</strong> in this period.@if ($metric === 'decisions') {{ $report['totals']['approved'] }} approved, {{ $report['totals']['rejected'] }} rejected.@endif</p>
                    @if ($total === 0)<p>No {{ strtolower($label) }} in the selected period.</p>@endif
                    <div class="content-activity-plot" aria-hidden="true">
                        @foreach ($report['daily'] as $day => $values)
                            <div class="content-activity-column" title="{{ $values['label'] }}: {{ $values[$metric] }}"><span style="height: {{ round(100 * $values[$metric] / $report['peak'], 2) }}%"></span></div>
                        @endforeach
                    </div>
                    <p class="content-activity-axis" aria-hidden="true"><span>{{ $report['start']->format('M j') }}</span><span>Scale: 0–{{ $report['peak'] }} per day</span><span>{{ $report['end']->format('M j') }}</span></p>
                @endif
            </section>
        @endforeach
    </div>
    <section class="dashboard-panel">
        <h2>How to read these counts</h2>
        <p>Submissions count existing posts by their original creation date, including pending, rejected, and archived posts. Drafts and deleted posts are excluded. Editing or resubmitting a post does not create another submission in this report.</p>
        <p>Review counts include recorded approval and rejection events. A post can have several decisions. Earlier unrecorded reviews and automatic publication are not counted. Only posts you can manage now are included, so permissions, ownership changes, or deletions can change past totals.</p>
        <details class="content-activity-data"><summary>Daily values</summary>
            <x-ui.data-table label="Daily content activity">
                <table class="dashboard-table"><thead><tr><th scope="col">Date</th><th scope="col">Submissions</th><th scope="col">Approvals</th><th scope="col">Rejections</th></tr></thead><tbody>
                    @foreach ($report['daily'] as $day => $values)
                        <tr><th scope="row">{{ $day }}</th><td>{{ $values['submissions'] }}</td><td>{{ $report['historyAvailable'] ? $values['approved'] : 'Not available' }}</td><td>{{ $report['historyAvailable'] ? $values['rejected'] : 'Not available' }}</td></tr>
                    @endforeach
                </tbody></table>
            </x-ui.data-table>
        </details>
        @if ($report['historyAvailable'])<a class="dashboard-text-button" href="{{ route('workspace.review-history.index', ['module' => $filters['module'], 'from' => $report['start']->toDateString(), 'to' => $report['end']->toDateString()]) }}">Browse decisions in this period</a>@endif
    </section>
</div>
@endsection
