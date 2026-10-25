<?php

namespace App\Http\Controllers;

use App\Models\OfficerTerm;
use App\Support\AcademicYear;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

/**
 * Shows public About content with current officers separated from dated historical snapshots.
 */
class AboutController extends Controller
{
    /** Current terms feed the top cards; every other year forms newest-first history. */
    public function __invoke(): View
    {
        $current = AcademicYear::current();
        $officers = Schema::hasTable('officer_terms')
            ? OfficerTerm::current()->inDisplayOrder()->get()
            : collect();

        if ($officers->isEmpty()) {
            $officers = collect([
                (object) ['name' => 'Khyle Alegre', 'position' => 'Adviser', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'Lance Carlo Bernabe', 'position' => 'Student Adviser', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'Mathica Vickean Sarmiento', 'position' => 'President', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'Irish Nicole Bersabe', 'position' => 'Vice President (Internal)', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'Charlette Sapnu', 'position' => 'Vice President (External)', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'Nicael P. San Diego', 'position' => 'Secretary', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'John Daniel Bayani', 'position' => 'Treasurer', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'Ayla Shannel Arreza', 'position' => 'Auditor', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'Chanel Jade Fernandez', 'position' => 'Public Information Officer', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'Robert John Gacita', 'position' => 'Business Manager', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'Janna Mino Dela Cruz', 'position' => 'Social Media Manager', 'photo_url' => asset('images/Wolf.png')],
                (object) ['name' => 'Fhiel A. Encabo', 'position' => 'Multimedia (Creative)', 'photo_url' => asset('images/Wolf.png')],
            ]);
        }

        $history = Schema::hasTable('officer_terms')
            ? OfficerTerm::past()->get()->groupBy('academic_year')->sortKeysDesc()
            : collect();

        if ($history->isEmpty()) {
            $history = collect([
                '2025-2026' => collect([
                    (object) ['name' => 'Khyle Alegre', 'position' => 'Adviser', 'photo_url' => asset('images/Wolf.png')],
                    (object) ['name' => 'Mathica Vickean Sarmiento', 'position' => 'President', 'photo_url' => asset('images/Wolf.png')],
                    (object) ['name' => 'Irish Nicole Bersabe', 'position' => 'Vice President (Internal)', 'photo_url' => asset('images/Wolf.png')],
                ]),
                '2024-2025' => collect([
                    (object) ['name' => 'Khyle Alegre', 'position' => 'Adviser', 'photo_url' => asset('images/Wolf.png')],
                    (object) ['name' => 'Lance Carlo Bernabe', 'position' => 'Student Adviser', 'photo_url' => asset('images/Wolf.png')],
                    (object) ['name' => 'Mathica Vickean Sarmiento', 'position' => 'President', 'photo_url' => asset('images/Wolf.png')],
                ]),
                '2023-2024' => collect([
                    (object) ['name' => 'Khyle Alegre', 'position' => 'Adviser', 'photo_url' => asset('images/Wolf.png')],
                    (object) ['name' => 'John Daniel Bayani', 'position' => 'Treasurer', 'photo_url' => asset('images/Wolf.png')],
                    (object) ['name' => 'Nicael P. San Diego', 'position' => 'Secretary', 'photo_url' => asset('images/Wolf.png')],
                ]),
            ]);
        } else {
            foreach ($history as $year => $terms) {
                // Keep unknown positions after configured roles, then use the saved order and name.
                $history[$year] = $terms->sortBy(function ($term): array {
                    $positionIndex = array_search($term->position, config('school.officer_positions', []), true);
                    if ($positionIndex === false) $positionIndex = 999;

                    return [$positionIndex, $term->sort_order, $term->name];
                })->values();
            }
        }

        return view('pages.about', compact('current', 'officers', 'history'));
    }
}
