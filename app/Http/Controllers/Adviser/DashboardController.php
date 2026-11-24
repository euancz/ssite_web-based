<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Document;
use App\Models\Liquidation;
use App\Models\User;
use App\Support\InitialPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Supplies role-assignment dashboards with counts only and no private post details. */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        if (InitialPageData::pending($request)) {
            return view('adviser.dashboard', ['pageDataPending' => true]);
        }

        $modules = [
            'articles' => ['label' => 'Articles', 'model' => Article::class],
            'activities' => ['label' => 'Activities', 'model' => Activity::class],
            'achievements' => ['label' => 'Achievements', 'model' => Achievement::class],
            'documents' => ['label' => 'Documents', 'model' => Document::class],
            'liquidations' => ['label' => 'Liquidation', 'model' => Liquidation::class],
        ];
        $pendingByModule = [];
        $pendingTotal = 0;
        foreach ($modules as $key => $module) {
            $count = $module['model']::pending()->count();
            $pendingByModule[$key] = ['label' => $module['label'], 'count' => $count];
            $pendingTotal += $count;
        }

        $presidentExists = User::query()->where('role', User::ROLE_OFFICER)->get(['id', 'role', 'officer_position'])
            ->contains(fn (User $user): bool => $user->isPresident());
        $advisers = auth()->user()->isAdmin()
            ? User::query()->where('role', User::ROLE_ADVISER)->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('adviser.dashboard', [
            'pendingByModule' => $pendingByModule,
            'pendingTotal' => $pendingTotal,
            'presidentExists' => $presidentExists,
            'totalOfficers' => User::query()->where('role', User::ROLE_OFFICER)->count(),
            'totalStudents' => User::query()->where('role', User::ROLE_STUDENT)->count(),
            'advisers' => $advisers,
        ]);
    }
}
