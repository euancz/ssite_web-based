<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ContentVersions
{
    public function available(): bool
    {
        return Schema::hasTable('content_versions');
    }

    /** Compare stored source values, preserving the distinction between absent and empty fields. */
    public function changes(string $before, string $after): array
    {
        $old = json_decode($before, true, 512, JSON_THROW_ON_ERROR);
        $new = json_decode($after, true, 512, JSON_THROW_ON_ERROR);
        $changes = [];
        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $field) {
            $hadField = array_key_exists($field, $old);
            $hasField = array_key_exists($field, $new);
            if ($hadField !== $hasField || ($old[$field] ?? null) !== ($new[$field] ?? null)) {
                $changes[] = ['field' => $field, 'before' => $old[$field] ?? null,
                    'after' => $new[$field] ?? null, 'had_field' => $hadField, 'has_field' => $hasField];
            }
        }

        return $changes;
    }

    /** Only editable text/metadata: never storage paths, file bytes, or workflow fields. */
    public function snapshot(Model $post): array
    {
        $fields = match ($post->getTable()) {
            'articles' => ['title', 'content', 'content_format'],
            'activities' => ['title', 'description', 'activity_date', 'location'],
            'achievements' => ['title', 'description', 'achievement_date', 'awardee', 'category'],
            'documents' => ['title', 'description', 'category', 'semester', 'academic_year'],
            'liquidations' => ['title', 'description', 'amount', 'report_date'],
            default => throw new \InvalidArgumentException('Unsupported content version module.'),
        };

        return array_intersect_key($post->getAttributes(), array_flip($fields));
    }

    /** Called inside PostFiles' locked save transaction; failures roll back the content write. */
    public function record(Model $post, ?Model $before): void
    {
        if (! $this->available()) {
            return;
        }
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Content versions require the content save transaction.');
        }
        // Use persisted defaults and database types, including optional schema fields.
        $post = $post->fresh();
        $snapshot = $this->snapshot($post);
        if ($before && $snapshot === $this->snapshot($before)) {
            return;
        }
        $query = DB::table('content_versions')->where('post_type', $post::POST_TYPE)->where('post_id', $post->getKey());
        if ($before && ! $query->exists()) {
            $this->insert($before, $this->snapshot($before), 'baseline', false);
        }
        $this->insert($post, $snapshot, $before ? 'edited' : 'created', true);
    }

    private function insert(Model $post, array $snapshot, string $kind, bool $attributeEditor): void
    {
        $editor = $attributeEditor ? auth()->user() : null;
        DB::table('content_versions')->insert([
            'post_type' => $post::POST_TYPE,
            'post_id' => $post->getKey(),
            'editor_user_id' => $editor?->getKey(),
            'editor_name' => $editor?->name,
            'kind' => $kind,
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
