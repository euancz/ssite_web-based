<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DiscoverController;
use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Document;
use App\Models\Liquidation;
use App\Models\OfficerTerm;
use App\Models\Sponsor;
use App\Services\PostImages;
use App\Support\DocumentAcademicYear;
use App\Support\MobilePost;
use App\Support\PrivatePdfResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\PostLike;
use App\Notifications\PostLiked;
use App\Models\User;
use App\Support\PostEngagement;

class ContentController extends Controller
{
    public function discover(Request $request)
    {
        $data = app(DiscoverController::class)($request)->getData();

        return response()->json(['data' => [
            'articles' => $data['articles']->map(fn ($post) => MobilePost::serialize($post, 'created_at'))->values(),
            'activities' => $data['activities']->map(fn ($post) => MobilePost::serialize($post, 'activity_date'))->values(),
            'achievements' => $data['achievements']->map(fn ($post) => MobilePost::serialize($post, 'achievement_date'))->values(),
        ]])->header('Cache-Control', 'no-store, private');
    }

    public function home()
    {
        $posts = static function (string $table, string $model, string $dateField, int $limit) {
            if (! Schema::hasTable($table)) {
                return [];
            }

            return $model::publiclyVisible()->with('author')->latest($dateField)->orderByDesc('id')->take($limit)->get()
                ->map(fn ($post) => MobilePost::serialize($post, $dateField))->values();
        };

        return response()->json([
            'data' => [
                'articles' => $posts('articles', Article::class, 'created_at', 1),
                'activities' => $posts('activities', Activity::class, 'activity_date', 1),
                'achievements' => $posts('achievements', Achievement::class, 'achievement_date', 1),
                'officers' => Schema::hasTable('officer_terms')
                    ? OfficerTerm::current()->inDisplayOrder()->get()->map(fn ($officer) => [
                        'name' => $officer->name, 'position' => $officer->position, 'photo_url' => $officer->photoUrl(),
                    ])->values() : [],
                'sponsors' => Sponsor::forDisplay()->map(fn ($sponsor) => $sponsor->forMobile())->values(),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function about()
    {
        $data = app(AboutController::class)()->getData();
        $serialize = static fn ($officer) => [
            'name' => $officer->name, 'position' => $officer->position, 'photo_url' => $officer->photoUrl(),
        ];

        return response()->json(['data' => [
            'academic_year' => $data['current'],
            'officers' => $data['officers']->map($serialize)->values(),
            'history' => $data['history']->map(fn ($officers) => $officers->map($serialize)->values()),
        ]])->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request, string $module)
    {
        $config = [
            'articles' => [Article::class, 'articles', 'created_at'],
            'activities' => [Activity::class, 'activities', 'activity_date'],
            'achievements' => [Achievement::class, 'achievements', 'achievement_date'],
            'documents' => [Document::class, 'documents', 'created_at'],
            'liquidations' => [Liquidation::class, 'liquidations', 'report_date'],
        ][$module] ?? abort(404);
        [$model, $table, $dateField] = $config;
        abort_unless(Schema::hasTable($table), 503, 'Content is not available yet.');

        $query = $model::publiclyVisible()->with('author');
        $engagement = app(PostEngagement::class);
        $likesAvailable = $engagement->likesAvailable();
        if ($likesAvailable && Schema::hasColumn($table, 'like_count')) {
            $query->withCount(['likes as liked_by_viewer' => fn ($likes) =>
                $likes->where('user_id', $request->attributes->get('mobile_user')->id)]);
        }
        $filters = [];
        if ($module === 'documents') {
            $filters['category'] = (clone $query)->whereNotNull('category')->where('category', '<>', '')
                ->distinct()->orderBy('category')->pluck('category')->values();
            foreach (['category', 'semester'] as $field) {
                $value = $request->query($field);
                if (is_string($value) && $value !== '') {
                    $query->where($field, $value);
                }
            }
            if (DocumentAcademicYear::available()) {
                $filters['academic_year'] = Document::publiclyVisible()->whereNotNull('academic_year')
                    ->distinct()->orderByDesc('academic_year')->pluck('academic_year')->values();
                $year = $request->query('academic_year');
                if ($year === 'unassigned') {
                    $query->whereNull('academic_year');
                } elseif (is_string($year) && $year !== '') {
                    $query->where('academic_year', $year);
                }
            }
        }
        $term = $request->query('q');
        $search = is_string($term) ? mb_substr(trim($term), 0, 100) : '';
        if ($search !== '') {
            $query->where(function ($builder) use ($search, $module) {
                \App\Support\SearchText::contains($builder, 'title', $search);
                if ($module === 'articles') {
                    \App\Support\SearchText::contains($builder, 'content', $search, 'or');
                } else {
                    \App\Support\SearchText::contains($builder, 'description', $search, 'or');
                }
            });
        }
        $sortMap = ['recent' => ['created_at', 'desc'], 'oldest' => ['created_at', 'asc'], 'title' => ['title', 'asc']];
        [$sortColumn, $sortDirection] = $sortMap[$request->query('sort', 'recent')] ?? $sortMap['recent'];
        $items = $query->orderBy($sortColumn, $sortDirection)->orderBy('id')
            ->paginate(min(max((int) $request->query('per_page', 10), 1), 50));
        $items->getCollection()->transform(fn ($post) => MobilePost::serialize($post, $dateField));

        return response()->json([
            'data' => $items->items(),
            'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total(), 'filters' => $filters],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function file(Request $request, string $module, int $id)
    {
        $model = $module === 'documents' ? Document::class : Liquidation::class;
        $post = $model::publiclyVisible()->findOrFail($id);
        Gate::forUser($request->attributes->get('mobile_user'))->authorize('viewFile', $post);
        $folder = $module === 'documents' ? 'documents' : 'liquidation';
        abort_unless(is_string($post->file_path)
            && preg_match('#\A'.$folder.'/[A-Za-z0-9]{40}\.pdf\z#D', $post->file_path)
            && Storage::disk('local')->exists($post->file_path), 404);

        return app(PrivatePdfResponse::class)->make($post->file_path, basename($post->original_name), $request);
    }

    public function media(string $module, int $id)
    {
        $model = [
            'articles' => Article::class,
            'activities' => Activity::class,
            'achievements' => Achievement::class,
        ][$module] ?? abort(404);
        $post = $model::publiclyVisible()->findOrFail($id);

        return app(PostImages::class)->response($post);
    }

    public function toggleLike(Request $request, string $module, int $id, PostEngagement $engagement)
    {
        abort_unless($engagement->likesAvailable(), 404);
        $validated = $request->validate([
            'liked' => ['required', 'string', 'in:0,1'],
        ]);

        $config = [
            'articles' => [Article::class, 'article'],
            'activities' => [Activity::class, 'activity'],
            'achievements' => [Achievement::class, 'achievement'],
            'documents' => [Document::class, 'document'],
            'liquidations' => [Liquidation::class, 'liquidation'],
        ][$module] ?? abort(404);
        [$model, $type] = $config;
        $user = $request->attributes->get('mobile_user');
        $desiredLike = $validated['liked'] === '1';
        $likeCount = 0;
        $liked = false;

        DB::transaction(function () use (&$likeCount, &$liked, $model, $type, $id, $user, $desiredLike): void {
            $post = $model::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($post->isApproved() && $post->content_status === 'active', 404);
            abort_unless(Schema::hasColumn($post->getTable(), 'like_count'), 404);
            Gate::forUser($user)->authorize('view', $post);

            $like = PostLike::query()
                ->where('post_type', $type)
                ->where('post_id', $id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($desiredLike && ! $like) {
                $like = new PostLike();
                $like->post_type = $type;
                $like->post_id = $id;
                $like->user_id = $user->id;
                $like->created_at = now();
                $like->save();
                DB::table($post->getTable())->whereKey($id)->increment('like_count');
                if ($post->created_by_user_id && (int) $post->created_by_user_id !== (int) $user->id) {
                    User::query()->find($post->created_by_user_id)?->notify(new PostLiked([
                        'post_type' => $type,
                        'post_id' => $id,
                        'title' => $post->title,
                        'actor_id' => $user->id,
                        'actor_name' => $user->name,
                        'url' => route(match ($type) {
                            'article' => 'articles.show',
                            'activity' => 'activities.show',
                            'achievement' => 'achievements.show',
                            'document' => 'documents.show',
                            default => 'liquidations.show',
                        }, $post),
                    ]));
                }
            } elseif (! $desiredLike && $like) {
                $like->delete();
                DB::table($post->getTable())->whereKey($id)->where('like_count', '>', 0)->decrement('like_count');
            }

            $liked = $desiredLike;
            $likeCount = (int) DB::table($post->getTable())->whereKey($id)->value('like_count');
        });

        return response()->json(['liked' => $liked, 'like_count' => $likeCount]);
    }
}
