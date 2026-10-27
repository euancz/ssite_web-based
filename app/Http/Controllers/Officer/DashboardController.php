<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Services\ContentDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ContentDashboard $dashboard): View
    {
        $user = $request->user();

        return view($user->isPresident() ? 'workspace.dashboard' : 'officer.dashboard', $dashboard->forUser($user));
    }
}
