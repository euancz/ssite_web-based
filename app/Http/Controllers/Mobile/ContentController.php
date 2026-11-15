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
use App\Services\FeaturedArticles;
use App\Services\PostImages;
use App\Support\DocumentAcademicYear;
use App\Support\MobilePost;
use App\Support\PrivatePdfResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

            return $model::publiclyVisible()->with('author')->latest($dateField)->take($limit)->get()
                ->map(fn ($post) => MobilePost::serialize($post, $dateField))->values();
        };

        return response()->json([
            'data' => [
                'articles' => Schema::hasTable('articles')
                    ? app(FeaturedArticles::class)->forDisplay(1)
                        ->map(fn ($post) => MobilePost::serialize($post, 'created_at'))->values()
                    : [],
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
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        if ($search !== '') {
            $query->where(function ($builder) use ($search, $module) {
                $builder->where('title', 'like', '%'.addcslashes($search, '%_\\').'%');
                if ($module === 'articles') {
                    $builder->orWhere('content', 'like', '%'.addcslashes($search, '%_\\').'%');
                } else {
                    $builder->orWhere('description', 'like', '%'.addcslashes($search, '%_\\').'%');
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
}
