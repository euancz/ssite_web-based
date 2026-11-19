<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Services\ContentDashboard;
use App\Support\InitialPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ContentDashboard $dashboard): View
    {
        $user = $request->user();

        $view = $user->isPresident() ? 'workspace.dashboard' : 'officer.dashboard';
        if (InitialPageData::pending($request)) {
            return view($view, [
                'pageDataPending' => true,
                'moduleStats' => $dashboard->modulesFor($user),
            ]);
        }

        return view($view, $dashboard->forUser($user));
    }
}
