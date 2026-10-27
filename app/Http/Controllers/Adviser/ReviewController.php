<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ContentModules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Lists current review decisions, not a historical audit trail. */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->canReview(), 403);
        $modules = ContentModules::all();
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'module' => ['nullable', Rule::in(array_keys($modules))],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'sort' => ['nullable', Rule::in(['oldest', 'recent', 'reviewed'])],
            'visibility' => ['nullable', Rule::in(['active', 'archived'])],
        ]);
        $status = $filters['status'] ?? 'pending';
        $filters['sort'] = $filters['sort'] ?? ($status === 'pending' ? 'oldest' : 'reviewed');
        $filters['q'] = trim($filters['q'] ?? '');
        $queries = [];
        foreach ($modules as $key => $module) {
            if (! empty($filters['module']) && $filters['module'] !== $key) {
                continue;
            }
            $model = new $module['model'];
            $table = $model->getTable();
            $query = DB::table($table)->selectRaw(
                '? as post_type, ? as type_label, ? as route_prefix, '.$model->getKeyName().' as post_id,
                title, created_by_user_id, created_at, approval_status as status, content_status,
                rejection_reason, reviewed_by_user_id, reviewed_at',
                [$model::POST_TYPE, $module['singular'], $key]
            );
            if ($filters['q'] !== '') {
                $query->where(function ($search) use ($filters, $table) {
                    $search->where('title', 'like', '%'.$filters['q'].'%')->orWhereExists(function ($author) use ($filters, $table) {
                        $author->selectRaw('1')->from('users')->whereColumn('users.user_id', $table.'.created_by_user_id')
                            ->where('users.name', 'like', '%'.$filters['q'].'%');
                    });
                });
            }
            if (! empty($filters['from'])) {
                $query->where('created_at', '>=', $filters['from'].' 00:00:00');
            }
            if (! empty($filters['to'])) {
                $query->where('created_at', '<', Carbon::parse($filters['to'])->addDay()->toDateString());
            }
            if (! empty($filters['visibility'])) {
                $query->where('content_status', $filters['visibility']);
            }
            $queries[] = $query;
        }
        $union = array_shift($queries);
        foreach ($queries as $query) {
            $union->unionAll($query);
        }
        $scoped = DB::query()->fromSub($union, 'review_posts');
        $statusCounts = (clone $scoped)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $posts = (clone $scoped)->where('status', $status);
        match ($filters['sort']) {
            'recent' => $posts->orderByDesc('created_at'),
            'reviewed' => $posts->orderByDesc('reviewed_at')->orderByDesc('created_at'),
            default => $posts->orderBy('created_at'),
        };
        // A stable tie-breaker prevents duplicate or missing rows across page boundaries.
        $posts = $posts->orderBy('post_type')->orderBy('post_id')->paginate(10)->appends($filters);
        $people = User::query()->whereIn('user_id', $posts->getCollection()->pluck('created_by_user_id')
            ->merge($posts->getCollection()->pluck('reviewed_by_user_id'))->filter()->unique())
            ->get(['user_id', 'name'])->keyBy('user_id');
        foreach ($posts as $post) {
            $post->author = $people->get($post->created_by_user_id);
            $post->reviewer = $people->get($post->reviewed_by_user_id);
            $post->preview_url = route('workspace.posts.'.$post->route_prefix.'.show', [$post->post_id]);
            $post->approve_url = route($post->route_prefix.'.approve', [$post->post_id]);
            $post->reject_url = route($post->route_prefix.'.reject', [$post->post_id]);
        }

        return view('adviser.reviews.index', ['posts' => $posts, 'activeStatus' => $status,
            'filters' => $filters, 'modules' => $modules, 'statusCounts' => $statusCounts]);
    }
}
