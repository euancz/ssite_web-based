<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Document;
use App\Models\Liquidation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Supplies role-assignment dashboards with counts only and no private post details. */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
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

        $presidentExists = User::query()->where('role', User::ROLE_OFFICER)->get(['user_id', 'role', 'officer_position'])
            ->contains(fn (User $user): bool => $user->isPresident());
        $advisers = auth()->user()->isSuperadmin()
            ? User::query()->where('role', User::ROLE_ADVISER)->orderBy('name')->get(['user_id', 'name', 'email'])
            : collect();
        $roleHistory = collect();
        if (auth()->user()->isSuperadmin()
            && Schema::hasColumns('role_changes', ['user_id', 'changed_by_user_id', 'old_role', 'new_role', 'old_officer_position', 'new_officer_position', 'created_at'])) {
            $roleHistory = DB::table('role_changes as changes')
                ->leftJoin('users as target', 'target.user_id', '=', 'changes.user_id')
                ->leftJoin('users as actor', 'actor.user_id', '=', 'changes.changed_by_user_id')
                ->orderByDesc('changes.created_at')
                ->limit(10)
                ->get([
                    'changes.old_role', 'changes.new_role', 'changes.old_officer_position',
                    'changes.new_officer_position', 'changes.created_at',
                    'target.name as target_name', 'actor.name as actor_name',
                ]);
        }

        return view('adviser.dashboard', [
            'pendingByModule' => $pendingByModule,
            'pendingTotal' => $pendingTotal,
            'presidentExists' => $presidentExists,
            'totalOfficers' => User::query()->where('role', User::ROLE_OFFICER)->count(),
            'totalStudents' => User::query()->where('role', User::ROLE_STUDENT)->count(),
            'advisers' => $advisers,
            'roleHistory' => $roleHistory,
        ]);
    }
}
