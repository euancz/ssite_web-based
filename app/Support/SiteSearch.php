<?php

namespace App\Support;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Article;
use App\Models\Document;
use App\Models\Liquidation;
use App\Models\OfficerTerm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Routing\Exceptions\UrlGenerationException;

/** Builds the shared public search result shape without querying user records. */
class SiteSearch
{
    private const TYPES = ['articles', 'activities', 'achievements', 'documents', 'liquidation', 'officers'];

    /** Normalize once, reject queries shorter than two characters, and enforce the shared word and character limits. */
    public function normalize(?string $query): array
    {
        $query = preg_replace('/[\x00-\x1F\x7F]/', ' ', (string) $query) ?? '';
        $query = preg_replace('/\s+/u', ' ', trim($query)) ?? trim($query);
        $query = mb_substr($query, 0, 100);
        $words = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return ['query' => $query, 'words' => array_slice($words, 0, 5), 'valid' => mb_strlen($query) >= 2];
    }

    /** Resolve guest visibility using search_include_documents_for_guests and search_include_liquidation_for_guests. */
    public function visibleTypes(bool $isGuest): array
    {
        $types = self::TYPES;
        if ($isGuest && ! config('school.search_include_documents_for_guests', config('school.documents_list_public', true))) {
            $types = array_diff($types, ['documents']);
        }
        if ($isGuest && ! config('school.search_include_liquidation_for_guests', config('school.liquidation_list_public', false))) {
            $types = array_diff($types, ['liquidation']);
        }

        return array_values($types);
    }

    /** Return merged visible matches, ordered by title relevance and then newest date. */
    public function results(string $query, array $words, array $types): Collection
    {
        $results = collect();
        foreach ($types as $type) {
            $results = $results->concat($this->{$type}($query, $words));
        }

        return $results->sort(function (array $left, array $right) use ($query): int {
            $rank = $this->relevanceRank($left['title'], $query) <=> $this->relevanceRank($right['title'], $query);
            if ($rank !== 0) return $rank;

            return ($right['_sort_date'] ?? 0) <=> ($left['_sort_date'] ?? 0);
        })->values()->map(function (array $item): array {
            unset($item['_sort_date']);
            return $item;
        });
    }

    /** Build a safely escaped HTML fragment; every source segment is escaped before markup is added. */
    public function highlight(string $text, array $words): string
    {
        $terms = array_values(array_unique(array_filter($words, fn ($word) => $word !== '')));
        if ($terms === []) return e($text);
        usort($terms, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $pattern = '/(' . implode('|', array_map(fn ($word) => preg_quote($word, '/'), $terms)) . ')/iu';
        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
        if (empty($matches[0])) return e($text);

        $fragment = '';
        $offset = 0;
        foreach ($matches[0] as [$match, $position]) {
            if ($position < $offset) continue;
            $fragment .= e(substr($text, $offset, $position - $offset));
            $fragment .= '<mark>' . e($match) . '</mark>';
            $offset = $position + strlen($match);
        }

        return $fragment . e(substr($text, $offset));
    }

    /** Articles search only their public title and body, never author or account fields. */
    private function articles(string $query, array $words): array
    {
        return $this->contentResults(Article::query()->publiclyVisible(), ['title', 'content'], 'articles', 'articles.show', 'article_id', 'created_at', $query, $words);
    }

    /** Activities use the persisted event date and location columns. */
    private function activities(string $query, array $words): array
    {
        return $this->contentResults(Activity::query()->publiclyVisible(), ['title', 'description', 'location'], 'activities', 'activities.show', 'activity_id', 'activity_date', $query, $words, fn ($row) => $row->location);
    }

    /** Achievements use their awardee and category metadata without joining user profiles. */
    private function achievements(string $query, array $words): array
    {
        return $this->contentResults(Achievement::query()->publiclyVisible(), ['title', 'description', 'awardee', 'category'], 'achievements', 'achievements.show', 'achievement_id', 'achievement_date', $query, $words, fn ($row) => trim(implode(' · ', array_filter([$row->category, $row->awardee]))));
    }

    /** Documents search public metadata only and never select or expose file_path. */
    private function documents(string $query, array $words): array
    {
        return $this->contentResults(Document::query()->publiclyVisible(), ['title', 'description', 'category'], 'documents', 'documents.show', 'document_id', 'created_at', $query, $words, fn ($row) => $row->category);
    }

    /** Liquidation searches title and description only; amounts and file paths are excluded. */
    private function liquidation(string $query, array $words): array
    {
        return $this->contentResults(Liquidation::query()->publiclyVisible(), ['title', 'description'], 'liquidation', 'liquidations.show', 'liquidation_id', 'report_date', $query, $words);
    }

    /** Officer snapshots are public About content and may outlive their linked user account. */
    private function officers(string $query, array $words): array
    {
        $builder = OfficerTerm::query();
        $this->applyWords($builder, ['name', 'position', 'academic_year'], $words);
        $this->applyTitleOrder($builder, 'name', $query);

        return $builder->get(['term_id', 'name', 'position', 'academic_year'])->map(function ($row): ?array {
            return $this->shape('officers', $row->name, $row->position, 'about', 'term_id', $row, null, $row->position . ' | ' . $row->academic_year);
        })->filter()->all();
    }

    /**
     * SECURITY: Callers pass each model's publiclyVisible() scope so pending and archived titles cannot leak.
     * Users are never joined; search fields and the shared result shape stay limited to public content.
     */
    private function contentResults(Builder $builder, array $fields, string $type, string $route, string $key, string $dateField, string $query, array $words, ?callable $meta = null): array
    {
        $this->applyWords($builder, $fields, $words);
        $this->applyTitleOrder($builder, 'title', $query);
        $rows = $builder->get(array_values(array_unique(array_merge($fields, [$key, $dateField, 'created_at']))));

        return $rows->map(function ($row) use ($fields, $type, $route, $key, $dateField, $meta, $words): ?array {
            $date = $row->{$dateField} ?? $row->created_at;
            $metaText = $meta ? (string) $meta($row) : '';
            return $this->shape($type, $row->title, $this->excerpt($fields, $row, $row->title, $words), $route, $key, $row, $date, $metaText);
        })->filter()->all();
    }

    /** SECURITY: Require each word in an allowed field, escape LIKE wildcards, and pass every pattern as a binding. */
    private function applyWords(Builder $builder, array $fields, array $words): void
    {
        foreach ($words as $word) {
            $pattern = '%' . $this->escapeLike($word) . '%';
            $builder->where(function (Builder $match) use ($fields, $pattern): void {
                foreach ($fields as $index => $field) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $match->{$method}($field, 'like', $pattern);
                }
            });
        }
    }

    /** Bind both CASE patterns so title-first relevance never interpolates user input into SQL. */
    private function applyTitleOrder(Builder $builder, string $titleField, string $query): void
    {
        $literal = $this->escapeLike($query);
        $builder->orderByRaw("CASE WHEN {$titleField} LIKE ? THEN 0 WHEN {$titleField} LIKE ? THEN 1 ELSE 2 END", [$literal . '%', '%' . $literal . '%']);
    }

    /** Backslash-escape LIKE metacharacters before adding the search wildcards. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** Strip markup and return a short plain-text excerpt centered around the first matched term. */
    private function excerpt(array $fields, object $row, string $title, array $words): string
    {
        $text = strip_tags($title);
        foreach ($fields as $field) {
            if ($field !== 'title' && trim((string) ($row->{$field} ?? '')) !== '') {
                $text .= ' · ' . strip_tags((string) $row->{$field});
            }
        }
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
        if (mb_strlen($text) <= 160) return $text;
        $positions = array_filter(array_map(fn ($word) => mb_stripos($text, $word), $words), fn ($position) => $position !== false);
        $cut = $positions === [] ? 0 : min($positions);
        $start = max(0, $cut - 50);
        $excerpt = mb_substr($text, $start, 160);
        return ($start > 0 ? '…' : '') . rtrim($excerpt) . '…';
    }

    /** Generate only named show-page URLs; missing routes are omitted instead of breaking search. */
    private function shape(string $type, string $title, string $excerpt, string $route, string $key, object $row, mixed $date, string $meta): ?array
    {
        try {
            $url = $route === 'about' ? route('about') : route($route, $row->{$key});
        } catch (UrlGenerationException) {
            return null;
        }

        return [
            'type' => $type,
            'title' => $title,
            'excerpt' => $excerpt,
            'url' => $url,
            'date' => $date ? (is_string($date) ? $date : $date->toDateString()) : null,
            'meta' => $meta,
            '_sort_date' => $date ? strtotime((string) $date) : 0,
        ];
    }

    /** Rank title prefixes, then title substrings, then other-field matches; newest records break ties. */
    private function relevanceRank(string $title, string $query): int
    {
        if (mb_stripos($title, $query) === 0) return 0;
        if (mb_stripos($title, $query) !== false) return 1;
        return 2;
    }
}
