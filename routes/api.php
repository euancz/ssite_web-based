<?php

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Document;
use App\Models\Liquidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Models\User;
use App\Http\Middleware\AuthenticateMobileToken;

/*
 | Mobile read API. It returns only fields from published, active public posts;
 | PDF files stay behind token authentication and the existing file policies.
 */
Route::prefix('mobile/v1')->name('api.mobile.')->group(function () {
    Route::post('/auth/exchange', function (Request $request) {
        $validated = $request->validate(['code' => ['required', 'string', 'size:64']]);
        $userId = Cache::pull('mobile_login_code:' . hash('sha256', $validated['code']));
        $user = $userId ? User::find($userId) : null;
        abort_unless($user && $user->hasCompletedProfile(), 401, 'Sign-in code is invalid or expired.');

        $tokenId = Str::random(48);
        $expiresAt = now()->addDays(30);
        Cache::put('mobile_access:' . $tokenId, $user->getKey(), $expiresAt);
        $token = Crypt::encryptString(json_encode([
            'sub' => $user->getKey(),
            'jti' => $tokenId,
            'exp' => $expiresAt->timestamp,
        ], JSON_THROW_ON_ERROR));

        return response()->json([
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => ['name' => $user->name, 'email' => $user->email],
        ]);
    })->middleware('throttle:10,1')->name('auth.exchange');

    Route::middleware(AuthenticateMobileToken::class)->group(function () {
    Route::post('/account/session', function (Request $request) {
        $validated = $request->validate(['destination' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Support\MobileAccountSession::DESTINATIONS))]]);
        return response()->json(['url' => \App\Support\MobileAccountSession::issue(
            $request->attributes->get('mobile_user'),
            $request->attributes->get('mobile_token_id'),
            $validated['destination'],
        )])->header('Cache-Control', 'no-store, private');
    })->middleware('throttle:10,1')->name('account.session');

    Route::get('/discover', function () {
        $data = app(\App\Http\Controllers\DiscoverController::class)()->getData();
        return response()->json(['data' => [
            'articles' => $data['articles']->map(fn ($post) => \App\Support\MobilePost::serialize($post, 'created_at'))->values(),
            'activities' => $data['activities']->map(fn ($post) => \App\Support\MobilePost::serialize($post, 'activity_date'))->values(),
            'achievements' => $data['achievements']->map(fn ($post) => \App\Support\MobilePost::serialize($post, 'achievement_date'))->values(),
        ]])->header('Cache-Control', 'no-store, private');
    })->name('discover');

    Route::get('/auth/me', function (Request $request) {
        $user = $request->attributes->get('mobile_user');
        return response()->json(['user' => [
            'name' => $user->name, 'email' => $user->email,
            'role' => $user->role, 'position' => $user->position(),
            'can_access_workspace' => \App\Support\MobileWorkspace::allowed($user),
        ]])->header('Cache-Control', 'no-store, private');
    })->name('auth.me');
    Route::post('/workspace/session', function (Request $request) {
        $url = \App\Support\MobileWorkspace::issue(
            $request->attributes->get('mobile_user'),
            $request->attributes->get('mobile_token_id'),
        );
        return response()->json(['url' => $url])->header('Cache-Control', 'no-store, private');
    })->middleware('throttle:10,1')->name('workspace.session');

    Route::post('/auth/logout', function (Request $request) {
        Cache::forget('mobile_access:' . $request->attributes->get('mobile_token_id'));
        return response()->json(['message' => 'Signed out.']);
    })->name('auth.logout');

    Route::get('/home', function () {
        $posts = static function (string $table, string $model, string $dateField, int $limit) {
            if (! Schema::hasTable($table)) return [];
            return $model::publiclyVisible()->with('author')->latest($dateField)->take($limit)->get()
                ->map(fn ($post) => \App\Support\MobilePost::serialize($post, $dateField))->values();
        };

        return response()->json([
            'data' => [
                'articles' => Schema::hasTable('articles')
                    ? app(\App\Services\FeaturedArticles::class)->forDisplay(1)
                        ->map(fn ($post) => \App\Support\MobilePost::serialize($post, 'created_at'))->values()
                    : [],
                'activities' => $posts('activities', Activity::class, 'activity_date', 1),
                'achievements' => $posts('achievements', Achievement::class, 'achievement_date', 1),
                'officers' => Schema::hasTable('officer_terms')
                    ? \App\Models\OfficerTerm::current()->inDisplayOrder()->get()->map(fn ($officer) => [
                        'name' => $officer->name, 'position' => $officer->position, 'photo_url' => $officer->photoUrl(),
                    ])->values() : [],
                'sponsors' => \App\Models\Sponsor::forDisplay()->map(fn ($sponsor) => $sponsor->forMobile())->values(),
            ],
        ])->header('Cache-Control', 'private, no-store');
    })->name('home');

    Route::get('/about', function () {
        $data = app(\App\Http\Controllers\AboutController::class)()->getData();
        $serialize = static fn ($officer) => [
            'name' => $officer->name, 'position' => $officer->position, 'photo_url' => $officer->photoUrl(),
        ];
        return response()->json(['data' => [
            'academic_year' => $data['current'],
            'officers' => $data['officers']->map($serialize)->values(),
            'history' => $data['history']->map(fn ($officers) => $officers->map($serialize)->values()),
        ]])->header('Cache-Control', 'private, no-store');
    })->name('about');

    Route::get('/{module}', function (Request $request, string $module) {
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
                if (is_string($value) && $value !== '') $query->where($field, $value);
            }
            if (\App\Support\DocumentAcademicYear::available()) {
                $filters['academic_year'] = Document::publiclyVisible()->whereNotNull('academic_year')
                    ->distinct()->orderByDesc('academic_year')->pluck('academic_year')->values();
                $year = $request->query('academic_year');
                if ($year === 'unassigned') $query->whereNull('academic_year');
                elseif (is_string($year) && $year !== '') $query->where('academic_year', $year);
            }
        }
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        if ($search !== '') {
            $query->where(function ($builder) use ($search, $module) {
                $builder->where('title', 'like', '%'.addcslashes($search, '%_\\').'%');
                if ($module === 'articles') $builder->orWhere('content', 'like', '%'.addcslashes($search, '%_\\').'%');
                else $builder->orWhere('description', 'like', '%'.addcslashes($search, '%_\\').'%');
            });
        }
        $sortMap = ['recent' => ['created_at', 'desc'], 'oldest' => ['created_at', 'asc'], 'title' => ['title', 'asc']];
        [$sortColumn, $sortDirection] = $sortMap[$request->query('sort', 'recent')] ?? $sortMap['recent'];
        $items = $query->orderBy($sortColumn, $sortDirection)->orderBy('id')
            ->paginate(min(max((int) $request->query('per_page', 10), 1), 50));
        $items->getCollection()->transform(fn ($post) => \App\Support\MobilePost::serialize($post, $dateField));

        return response()->json([
            'data' => $items->items(),
            'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total(), 'filters' => $filters],
        ])->header('Cache-Control', 'private, no-store');
    })->where('module', 'articles|activities|achievements|documents|liquidations')->name('content');

    Route::get('/files/{module}/{id}', function (Request $request, string $module, int $id) {
        $model = $module === 'documents' ? Document::class : Liquidation::class;
        $post = $model::publiclyVisible()->findOrFail($id);
        \Illuminate\Support\Facades\Gate::forUser($request->attributes->get('mobile_user'))->authorize('viewFile', $post);
        $folder = $module === 'documents' ? 'documents' : 'liquidation';
        abort_unless(is_string($post->file_path)
            && preg_match('#\A' . $folder . '/[A-Za-z0-9]{40}\.pdf\z#D', $post->file_path)
            && Storage::disk('local')->exists($post->file_path), 404);
        return app(\App\Support\PrivatePdfResponse::class)->make($post->file_path, basename($post->original_name), $request);
    })->where(['module' => 'documents|liquidations', 'id' => '[0-9]+'])->name('file');

    Route::get('/media/{module}/{id}', function (string $module, int $id) {
        $model = [
            'articles' => Article::class,
            'activities' => Activity::class,
            'achievements' => Achievement::class,
        ][$module] ?? abort(404);
        $post = $model::publiclyVisible()->findOrFail($id);
        return app(\App\Services\PostImages::class)->response($post);
    })->where(['module' => 'articles|activities|achievements', 'id' => '[0-9]+'])
        ->name('media');
    });
});

