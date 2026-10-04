<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class OfficerDashboardController extends Controller
{
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
