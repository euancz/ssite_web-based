<?php

namespace App\Http\Controllers;

use App\Models\OfficerTerm;
use App\Support\AcademicYear;
use Illuminate\Contracts\View\View;

/**
 * Shows public About content with current officers separated from dated historical snapshots.
 */
class AboutController extends Controller
{
    /** Current terms feed the top cards; every other year forms newest-first history. */
    public function __invoke(): View
    {
        $current = AcademicYear::current();
        $officers = OfficerTerm::current()->inDisplayOrder()->get();
        $history = OfficerTerm::past()->get()->groupBy('academic_year')->sortKeysDesc();
        foreach ($history as $year => $terms) {
            // Keep unknown positions after configured roles, then use the saved order and name.
            $history[$year] = $terms->sortBy(function ($term): array {
                $positionIndex = array_search($term->position, config('school.officer_positions', []), true);
                if ($positionIndex === false) $positionIndex = 999;

                return [$positionIndex, $term->sort_order, $term->name];
            })->values();
        }

        return view('pages.about', compact('current', 'officers', 'history'));
    }
}
