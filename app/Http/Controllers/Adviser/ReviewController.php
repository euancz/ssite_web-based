<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Lists review records only for the President-authorized review route. */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])]]);
        $status = $filters['status'] ?? 'pending';
        $modules = [
            ['table' => 'articles', 'key' => 'article_id', 'type' => 'article', 'label' => 'Article', 'route' => 'articles'],
            ['table' => 'activities', 'key' => 'activity_id', 'type' => 'activity', 'label' => 'Activity', 'route' => 'activities'],
            ['table' => 'achievements', 'key' => 'achievement_id', 'type' => 'achievement', 'label' => 'Achievement', 'route' => 'achievements'],
            ['table' => 'documents', 'key' => 'document_id', 'type' => 'document', 'label' => 'Document', 'route' => 'documents'],
            ['table' => 'liquidations', 'key' => 'liquidation_id', 'type' => 'liquidation', 'label' => 'Liquidation', 'route' => 'liquidations'],
        ];
        // Filter each module before combining it so each status tab reflects the same saved review state.
        $queries = array_map(function (array $module) use ($status): Builder {
            return DB::table($module['table'])
                ->selectRaw(
                    '? as post_type, ? as type_label, ? as route_prefix, ' . $module['key'] . ' as post_id, title, created_by_user_id, created_at, approval_status as status, rejection_reason',
                    [$module['type'], $module['label'], $module['route']]
                )
                ->where('approval_status', $status);
        }, $modules);
        $union = array_shift($queries);
        foreach ($queries as $query) $union->unionAll($query);

        $posts = DB::query()->fromSub($union, 'review_posts')
            ->orderByDesc('created_at')->paginate(5)->withQueryString();
        $authors = User::query()->whereIn('user_id', $posts->getCollection()->pluck('created_by_user_id')->filter()->unique())
            ->get(['user_id', 'name'])->keyBy('user_id');
        foreach ($posts as $post) {
            $post->author = $authors->get($post->created_by_user_id);
            $post->preview_url = route($post->route_prefix . '.show', [$post->post_id]);
            $post->approve_url = route($post->route_prefix . '.approve', [$post->post_id]);
            $post->reject_url = route($post->route_prefix . '.reject', [$post->post_id]);
        }

        return view('adviser.reviews.index', ['posts' => $posts, 'activeStatus' => $status]);
    }
}
