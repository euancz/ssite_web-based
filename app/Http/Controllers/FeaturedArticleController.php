<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\FeaturedArticles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FeaturedArticleController extends Controller
{
    public function __invoke(Request $request, Article $article, FeaturedArticles $features): RedirectResponse
    {
        Gate::authorize('feature', $article);
        abort_unless($features->available(), 404);
        $validated = $request->validate([
            'featured' => ['required', 'boolean'],
            'expected_featured_at' => ['present', 'nullable', 'date_format:Y-m-d H:i:s'],
        ]);
        $selected = (bool) $validated['featured'];

        DB::transaction(function () use ($article, $validated, $selected): void {
            $current = Article::query()->lockForUpdate()->findOrFail($article->getKey());
            Gate::authorize('feature', $current);
            if ($current->getRawOriginal('featured_at') !== $validated['expected_featured_at']) {
                throw ValidationException::withMessages(['featured' => 'The featured selection changed. Reload this article before trying again.']);
            }
            if ($selected && (! $current->isApproved() || $current->content_status !== Article::CONTENT_ACTIVE)) {
                throw ValidationException::withMessages(['featured' => 'Only published articles can be featured.']);
            }
            // Repeated selection does not reorder a feature or alter content/review timestamps.
            $current->featured_at = $selected ? ($current->featured_at ?? now()) : null;
            $current->timestamps = false;
            $current->save();
        });

        return redirect()->route('workspace.posts.articles.show', $article)
            ->with('status', $selected ? 'The article is selected for Home and Discover.' : 'The article was removed from featured selections.');
    }
}
