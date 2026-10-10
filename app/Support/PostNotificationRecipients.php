<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\PostApproved;
use App\Notifications\PostPublished;
use App\Notifications\PostRejected;
use App\Notifications\PostSubmittedForReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/** Builds role-based recipients and sends synchronous, failure-isolated post notifications. */
class PostNotificationRecipients
{
    private const POST_ROUTES = [
        'article' => ['articles.show', 'article_id'],
        'activity' => ['activities.show', 'activity_id'],
        'achievement' => ['achievements.show', 'achievement_id'],
        'document' => ['documents.show', 'document_id'],
        'liquidation' => ['liquidations.show', 'liquidation_id'],
    ];

    public function submitted(string $postType, Model $post, User $actor, bool $needsReviewAgain = false): void
    {
        $this->safely(function () use ($postType, $post, $actor, $needsReviewAgain): void {
            $this->sendToRole('adviser', $actor,
                new PostSubmittedForReview($this->payload($postType, $post, $actor), $needsReviewAgain));
        }, $postType, $post);
    }

    public function published(string $postType, Model $post, User $actor, array $exceptUserIds = []): void
    {
        // A publication notice requires both states; archiving and later restoring are never publication events.
        if ($post->getAttribute('approval_status') !== 'approved' || $post->getAttribute('content_status') !== 'active') return;
        $this->safely(function () use ($postType, $post, $actor, $exceptUserIds): void {
            $this->sendToEveryoneExcept(array_merge([$actor->user_id], $exceptUserIds),
                new PostPublished($this->payload($postType, $post, $actor)));
        }, $postType, $post);
    }

    public function approved(string $postType, Model $post, User $actor): void
    {
        $this->safely(function () use ($postType, $post, $actor): void {
            $payload = $this->payload($postType, $post, $actor);
            $author = $this->author($post);
            if ($author) $this->safely(fn () => $author->notify(new PostApproved($payload)), $postType, $post);
            if ($post->getAttribute('approval_status') === 'approved' && $post->getAttribute('content_status') === 'active') {
                // The author already receives the approval notice, so they are omitted from the new-post broadcast.
                $this->published($postType, $post, $actor, $author ? [$author->user_id] : []);
            }
        }, $postType, $post);
    }

    public function rejected(string $postType, Model $post, User $actor, string $reason): void
    {
        $this->safely(function () use ($postType, $post, $actor, $reason): void {
            $author = $this->author($post);
            if (! $author) return;
            $payload = $this->payload($postType, $post, $actor);
            $payload['rejection_reason'] = Str::limit(trim($reason), 255);
            $this->safely(fn () => $author->notify(new PostRejected($payload)), $postType, $post);
        }, $postType, $post);
    }

    private function payload(string $postType, Model $post, User $actor): array
    {
        [$route, $key] = self::POST_ROUTES[$postType];
        return [
            'post_type' => $postType,
            'post_id' => (int) $post->getAttribute($key),
            'title' => Str::limit((string) $post->getAttribute('title'), 100),
            'actor_name' => (string) $actor->name,
            'url' => route($route, $post->getAttribute($key)),
        ];
    }

    private function author(Model $post): ?User
    {
        $authorId = $post->getAttribute('created_by_user_id');
        return $authorId ? User::query()->find($authorId) : null;
    }

    private function sendToRole(string $role, User $actor, object $notification): void
    {
        User::query()->where('role', $role)->where('user_id', '<>', $actor->user_id)
            ->chunkById(100, function ($users) use ($notification): void {
                foreach ($users as $user) $this->safely(fn () => $user->notify($notification), 'post', null);
            }, 'user_id');
    }

    private function sendToEveryoneExcept(array $excludedIds, object $notification): void
    {
        User::query()->whereIn('role', ['student', 'officer', 'adviser'])
            ->when($excludedIds !== [], fn ($query) => $query->whereNotIn('user_id', array_unique($excludedIds)))
            // SECURITY: The broadcast is chunked by the custom user_id key to bound memory use.
            ->chunkById(100, function ($users) use ($notification): void {
                foreach ($users as $user) $this->safely(fn () => $user->notify($notification), 'post', null);
            }, 'user_id');
    }

    private function safely(callable $send, string $postType, ?Model $post): void
    {
        // SECURITY: Logging notification failures here keeps a successful post or review action successful.
        try {
            $send();
        } catch (Throwable $exception) {
            Log::warning('Post notification could not be sent.', [
                'post_type' => $postType,
                'post_id' => $post?->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
