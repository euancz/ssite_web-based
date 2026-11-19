<?php

namespace App\Services;

use App\Models\{Article, Activity, Achievement, Document, Liquidation, OfficerTerm, Sponsor};
use App\Support\MobilePost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Storage};

final class MobilePublisher
{
    public const MODELS = [Article::class, Activity::class, Achievement::class, Document::class, Liquidation::class, OfficerTerm::class, Sponsor::class];

    public function remove(Model $post): void
    {
        DB::table('mobile_publications')->where('module', $this->module($post))->where('record_id', $post->getKey())->delete();
    }

    private function module(Model $post): string
    {
        return $post instanceof OfficerTerm ? 'officers' : $post->getTable();
    }

    public function publish(Model $post): void
    {
        $module = $this->module($post);
        if (($post instanceof Sponsor && ! $post->is_active)
            || (! $post instanceof Sponsor && ! $post instanceof OfficerTerm && ($post->approval_status !== 'approved' || $post->content_status !== 'active'))) {
            $this->remove($post);
            return;
        }
        if ($post instanceof Sponsor) {
            $payload = $post->only(['id', 'name', 'description', 'website_url']);
            $payload['logo_path'] = $post->logo_path ? $this->copy('public', $post->logo_path) : null;
            $order = $post->sort_order;
        } elseif ($post instanceof OfficerTerm) {
            $position = array_search($post->position, config('school.officer_positions', []), true);
            $payload = ['name' => $post->name, 'position' => $post->position,
                'academic_year' => $post->academic_year, 'photo_path' => $this->photo($post)];
            $order = ($position === false ? 999 : $position) * 1000 + $post->sort_order;
        } else {
            $dateField = match ($module) {
                'activities' => 'activity_date', 'achievements' => 'achievement_date',
                'liquidations' => 'report_date', default => 'created_at',
            };
            $post->load('author');
            $payload = MobilePost::serialize($post, $dateField);
            unset($payload['image_url'], $payload['file_url'], $payload['url']);
            $payload['image_path'] = PostImages::exists($post)
                ? $this->copy(PostImages::disk($post->image), $post->image) : null;
            $payload['file_path'] = null;
            if (in_array($module, ['documents', 'liquidations'], true)) {
                $folder = $module === 'documents' ? 'documents' : 'liquidation';
                if (! preg_match('#\A'.$folder.'/[A-Za-z0-9]{40}\.pdf\z#D', (string) $post->file_path)) {
                    throw new \RuntimeException('Invalid mobile PDF path.');
                }
                $payload['file_path'] = $this->copy('local', $post->file_path);
            }
            $order = 0;
        }
        DB::table('mobile_publications')->updateOrInsert(['module' => $module, 'record_id' => $post->getKey()], [
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'title' => $payload['title'] ?? $post->name ?? '',
            'search_text' => ($payload['title'] ?? '').' '.($payload['description'] ?? ''),
            'category' => $post->category, 'semester' => $post->semester,
            'academic_year' => $post->academic_year, 'sort_date' => $post->created_at,
            'sort_order' => $order, 'updated_at' => now(),
        ]);
    }

    private function copy(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);
        if (! is_resource($stream)) throw new \RuntimeException('Cannot read mobile attachment: '.$path);
        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);
            $target = 'published/'.hash_final($hash).'.'.strtolower(pathinfo($path, PATHINFO_EXTENSION));
            rewind($stream);
            if (! Storage::disk('mobile')->exists($target)
                && ! Storage::disk('mobile')->put($target, $stream, ['ContentType' => Storage::disk($disk)->mimeType($path)])) {
                throw new \RuntimeException('Mobile attachment upload failed.');
            }
            return $target;
        } finally {
            fclose($stream);
        }
    }

    private function photo(OfficerTerm $post): ?string
    {
        $path = trim((string) $post->photo);
        if ($path === '' || preg_match('#^https?://#i', $path)) return null;
        if (str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, '\\')) return null;
        if (is_file(public_path($path))) {
            Storage::set('mobile-photo-source', Storage::build(['driver' => 'local', 'root' => public_path(), 'throw' => true]));
            return $this->copy('mobile-photo-source', $path);
        }
        return Storage::disk('public')->exists($path) ? $this->copy('public', $path) : null;
    }
}
