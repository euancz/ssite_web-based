<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        // TODO: Replace the pending-post list and monthly approvals count when post storage exists.
        return view('adviser.dashboard', [
            'pendingPosts' => collect(),
            'approvedThisMonth' => 0,
            'totalOfficers' => User::query()->where('role', 'officer')->count(),
            'totalStudents' => User::query()->where('role', 'student')->count(),
        ]);
    }
}
