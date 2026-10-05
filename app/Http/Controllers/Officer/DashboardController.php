<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Supplies the officer dashboard with the current placeholder post statistics.
 */
class DashboardController extends Controller
{
    /**
     * Render the officer dashboard; post-backed values await post storage.
     */
    public function __invoke(): View
    {
        // TODO: Replace the empty collection and zero counts when post storage is implemented.
        return view('officer.dashboard', [
            'posts' => collect(),
            'postCounts' => [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
            ],
        ]);
    }
}
