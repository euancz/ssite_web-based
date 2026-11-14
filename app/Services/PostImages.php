<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PostImages
{
    public static function disk(?string $path): string
    {
        return str_starts_with((string) $path, 'post-media/') ? 'local' : 'public';
    }

    public static function valid(?string $path, string $module): bool
    {
        return in_array($module, ['articles', 'activities', 'achievements'], true)
            && is_string($path)
            && preg_match('#\A(?:post-media/)?'.preg_quote($module, '#').'/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)\z#iD', $path) === 1;
    }

    public static function exists(Model $post): bool
    {
        return self::valid($post->image, $post->getTable()) && Storage::disk(self::disk($post->image))->exists($post->image);
    }

    public static function url(Model $post): string
    {
        return self::exists($post)
            ? route('posts.media', ['module' => $post->getTable(), 'id' => $post->getKey()])
            : asset(config('school.default_profile_picture', 'images/Wolf.png'));
    }

    public function response(Model $post): StreamedResponse
    {
        abort_unless(self::exists($post), 404);
        return Storage::disk(self::disk($post->image))->response($post->image, basename($post->image), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
