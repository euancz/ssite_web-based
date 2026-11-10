<?php

namespace App\Support;

use App\Models\Article;
use Illuminate\Support\Facades\Storage;

class MobilePost
{
    public static function serialize($post, string $dateField): array
    {
        $description = $post instanceof Article ? $post->displayText() : (string) ($post->description ?? '');
        $module = match (class_basename($post)) {
            'Article' => 'articles',
            'Activity' => 'activities',
            'Document' => 'documents',
            'Liquidation' => 'liquidations',
            default => 'achievements',
        };
        $imageUrl = method_exists($post, 'imageUrl') ? $post->imageUrl() : null;
        if (is_string($post->image) && $post->image !== '' && Storage::disk('public')->exists($post->image)) {
            $imageUrl = route('api.mobile.media', ['module' => $module, 'id' => $post->getKey()]);
        }
    
        return [
            'id' => $post->getKey(),
            'title' => (string) $post->title,
            'module' => $module,
            'author' => $post->author?->name ?? 'Former member',
            'author_role' => $post->author?->role === 'adviser' ? 'Adviser' : $post->author?->officer_position,
            'created_at' => $post->created_at?->toIso8601String(),
            'description' => $description,
            'image_url' => $imageUrl,
            'date' => $post->{$dateField}?->toDateString(),
            'category' => $post->category ?? null,
            'location' => $post->location ?? null,
            'awardee' => $post->awardee ?? null,
            'amount' => $post->amount ?? null,
            'academic_year' => $post->academic_year ?? null,
            'semester' => $post->semester ?? null,
            'file_url' => in_array($module, ['documents', 'liquidations'], true)
                ? route('api.mobile.file', ['module' => $module, 'id' => $post->getKey()]) : null,
            'url' => match (class_basename($post)) {
                'Article' => route('articles.show', $post),
                'Activity' => route('activities.show', $post),
                'Document' => route('documents.show', $post),
                'Liquidation' => route('liquidations.show', $post),
                default => route('achievements.show', $post),
            },
        ];
    }
}
