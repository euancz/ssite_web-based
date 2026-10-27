<?php

namespace App\Services;

use App\Models\User;
use App\Support\ContentModules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

final class ContentReviewHistory
{
    public function available(): bool
    {
        return Schema::hasTable('content_review_events');
    }

    /** Called only by the locked decision transaction, never from submitted form fields. */
    public function record(Model $post, User $reviewer, bool $approve, ?string $reason): void
    {
        if (! $this->available()) {
            return;
        }
        $this->assertSupported($post);
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Review history must be recorded inside the decision transaction.');
        }
        Gate::forUser($reviewer)->authorize($approve ? 'approve' : 'reject', $post);
        DB::table('content_review_events')->insert([
            'post_type' => $post::POST_TYPE,
            'post_id' => $post->getKey(),
            'reviewer_user_id' => $reviewer->getKey(),
            'reviewer_name' => $reviewer->name,
            'post_title' => $post->title,
            'decision' => $approve ? 'approved' : 'rejected',
            'reason' => $approve ? null : $reason,
            'created_at' => $post->reviewed_at,
        ]);
    }

    /** History is private to current editors (owner with module capability or President). */
    public function recentFor(Model $post, User $viewer): Collection
    {
        $this->assertSupported($post);
        Gate::forUser($viewer)->authorize('update', $post);
        if (! $this->available()) {
            return collect();
        }

        return DB::table('content_review_events')->where('post_type', $post::POST_TYPE)
            ->where('post_id', $post->getKey())->orderByDesc('id')->limit(20)->get();
    }

    private function assertSupported(Model $post): void
    {
        if (! in_array($post::class, array_column(ContentModules::all(), 'model'), true)) {
            throw new \InvalidArgumentException('Unsupported review-history module.');
        }
    }

    public function modulesFor(User $viewer): array
    {
        return array_filter(ContentModules::all(), fn ($module, $key) => $viewer->isPresident() || $viewer->canPostIn($key), ARRAY_FILTER_USE_BOTH);
    }

    /** Scope before searching or paginating. Deleted posts and lost capabilities never expose history. */
    public function visibleQuery(User $viewer): Builder
    {
        $modules = $this->modulesFor($viewer);
        abort_if($modules === [], 403);

        return DB::table('content_review_events as events')->where(function (Builder $scope) use ($modules, $viewer): void {
            foreach ($modules as $module) {
                $model = new $module['model'];
                $scope->orWhere(function (Builder $type) use ($model, $viewer): void {
                    $type->where('events.post_type', $model::POST_TYPE)
                        ->whereExists(function (Builder $post) use ($model, $viewer): void {
                            $post->selectRaw('1')->from($model->getTable().' as current_post')
                                ->whereColumn('current_post.'.$model->getKeyName(), 'events.post_id');
                            if (! $viewer->isPresident()) {
                                $post->where('current_post.created_by_user_id', $viewer->getKey());
                            }
                        });
                });
            }
        });
    }
}
