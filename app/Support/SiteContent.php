<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SiteContent
{
    public function section(string $section): array
    {
        $sections = config('site_sections');
        abort_unless(isset($sections[$section]), 404);

        return $sections[$section];
    }

    public function saved(string $section): array
    {
        $attributes = request()->attributes;
        if (! $attributes->has('site_section_rows')) {
            $rows = Schema::hasTable('site_sections')
                ? DB::table('site_sections')->get()
                : [];
            $saved = [];
            foreach ($rows as $row) {
                $saved[$row->section] = [
                    'content' => json_decode($row->content, true, flags: JSON_THROW_ON_ERROR),
                    'revision' => (int) $row->revision,
                ];
            }
            $attributes->set('site_section_rows', $saved);
        }

        return $attributes->get('site_section_rows')[$section] ?? ['content' => [], 'revision' => 0];
    }

    public function value(string $section, string $field): string
    {
        $default = $this->section($section)['fields'][$field];
        if ($section === 'landing-app' && $field === 'download_url') {
            $default = (string) config('mobile.download_url');
        }

        return $this->saved($section)['content'][$field] ?? $default;
    }

    public function image(string $section, string $field): string
    {
        $path = $this->saved($section)['content'][$field] ?? null;

        return $path ? Storage::disk('public')->url($path) : asset($this->section($section)['fields'][$field]);
    }
}
