@props(['article'])

@can('feature', $article)
    @if (array_key_exists('featured_at', $article->getAttributes()))
        <section class="workspace-review-summary workspace-article-feature" aria-labelledby="article-feature-heading">
            <h2 id="article-feature-heading">Featured article</h2>
            <p>Selected articles take priority in Home and Discover, most recently selected first. Other spaces use recent articles.</p>
            @if ($article->featured_at)
                <p>Selected {{ $article->featured_at->format('M j, Y') }}. This selection appears only while the article is published and active.</p>
            @elseif (! $article->isApproved() || $article->isArchived())
                <p>Publish this article before featuring it.</p>
            @endif
            @error('featured')<p class="dashboard-error" role="alert">{{ $message }}</p>@enderror
            @error('expected_featured_at')<p class="dashboard-error" role="alert">{{ $message }}</p>@enderror
            @if ($article->featured_at || ($article->isApproved() && ! $article->isArchived()))
                <form method="POST" action="{{ route('workspace.posts.articles.featured', $article) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="expected_featured_at" value="{{ $article->getRawOriginal('featured_at') }}">
                    <button type="submit" class="dashboard-button dashboard-button-secondary" name="featured" value="{{ $article->featured_at ? '0' : '1' }}">
                        {{ $article->featured_at ? 'Remove featured selection' : 'Feature on Home and Discover' }}
                    </button>
                </form>
            @endif
        </section>
    @endif
@endcan
