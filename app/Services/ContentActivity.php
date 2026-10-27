<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

final class ContentActivity
{
    public function forUser(User $viewer, int $days, ?string $module = null): array
    {
        $history = app(ContentReviewHistory::class);
        $modules = $history->modulesFor($viewer);
        abort_if($modules === [], 403);
        if (! in_array($days, [7, 30], true) || ($module !== null && ! isset($modules[$module]))) {
            throw new \InvalidArgumentException('Unsupported content activity filters.');
        }
        $end = Carbon::now();
        $start = $end->copy()->startOfDay()->subDays($days - 1);
        $daily = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $daily[$day->toDateString()] = ['label' => $day->format('M j'), 'submissions' => 0, 'approved' => 0, 'rejected' => 0];
        }
        foreach ($modules as $key => $metadata) {
            if ($module !== null && $key !== $module) {
                continue;
            }
            $query = $metadata['model']::query()->whereBetween('created_at', [$start, $end]);
            if (! $viewer->isPresident()) {
                $query->where('created_by_user_id', $viewer->getKey());
            }
            foreach ($query->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->get() as $row) {
                if (isset($daily[$row->day])) {
                    $daily[$row->day]['submissions'] += (int) $row->total;
                }
            }
        }
        $historyAvailable = $history->available();
        if ($historyAvailable) {
            $query = $history->visibleQuery($viewer)->whereBetween('events.created_at', [$start, $end]);
            if ($module !== null) {
                $query->where('events.post_type', $modules[$module]['model']::POST_TYPE);
            }
            $rows = $query->selectRaw('DATE(events.created_at) as day, events.decision, COUNT(*) as total')
                ->whereIn('events.decision', ['approved', 'rejected'])->groupByRaw('DATE(events.created_at), events.decision')->get();
            foreach ($rows as $row) {
                if (isset($daily[$row->day])) {
                    $daily[$row->day][$row->decision] = (int) $row->total;
                }
            }
        }
        $totals = ['submissions' => 0, 'approved' => 0, 'rejected' => 0];
        $peak = 1;
        foreach ($daily as &$row) {
            $row['decisions'] = $row['approved'] + $row['rejected'];
            foreach (array_keys($totals) as $metric) {
                $totals[$metric] += $row[$metric];
            }
            $peak = max($peak, $row['submissions'], $row['decisions']);
        }
        unset($row);

        return compact('daily', 'totals', 'peak', 'historyAvailable', 'start', 'end');
    }
}
