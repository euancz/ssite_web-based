<?php

namespace App\Http\Controllers\Adviser;

use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class SiteSectionController
{
    public function edit(string $section, SiteContent $content): View
    {
        return view('adviser.site-section', [
            'section' => $section,
            'definition' => $content->section($section),
            'revision' => $content->saved($section)['revision'],
        ]);
    }

    public function update(Request $request, string $section, SiteContent $content): RedirectResponse
    {
        $definition = $content->section($section);
        $saved = $content->saved($section);
        $fields = array_keys($definition['fields']);
        $rules = ['revision' => 'required|integer|min:0', 'fields' => 'required|array:'.implode(',', $fields)];
        foreach ($fields as $field) {
            $rules["fields.$field"] = match (true) {
                str_ends_with($field, '_image') => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096|dimensions:max_width=6000,max_height=6000',
                $field === 'download_url' => 'present|nullable|string|url:http,https|max:2048',
                str_ends_with($field, '_url') => 'required|string|url:http,https|max:2048',
                $field === 'contact_email' => 'required|email|max:254',
                default => 'required|string|max:6000',
            };
        }
        $validated = $request->validate($rules);
        $revision = (int) $validated['revision'];
        $conflict = 'Another adviser saved this section. Reload the editor to see their changes.';
        if ($revision !== $saved['revision']) {
            throw ValidationException::withMessages(['revision' => $conflict]);
        }

        $values = $saved['content'];
        $uploads = [];
        $obsolete = [];
        try {
            foreach ($validated['fields'] as $field => $value) {
                if (str_ends_with($field, '_image')) {
                    if (! $value) {
                        continue;
                    }
                    $path = $value->store('site-sections', 'public');
                    if (! $path) {
                        throw new RuntimeException('Could not save section image.');
                    }
                    $uploads[] = $path;
                    if (isset($values[$field])) {
                        $obsolete[] = $values[$field];
                    }
                    $value = $path;
                }
                $values[$field] = $value ?? '';
            }

            $row = ['content' => json_encode($values, JSON_THROW_ON_ERROR), 'revision' => $revision + 1];
            $table = DB::table('site_sections');
            $written = $revision === 0
                ? $table->insertOrIgnore(['section' => $section] + $row)
                : $table->where(['section' => $section, 'revision' => $revision])->update($row);
            if (! $written) {
                throw ValidationException::withMessages(['revision' => $conflict]);
            }
        } catch (Throwable $error) {
            if ($uploads) {
                Storage::disk('public')->delete($uploads);
            }
            throw $error;
        }
        if ($obsolete) {
            Storage::disk('public')->delete($obsolete);
        }

        return redirect()->to(route($definition['route']).'#edit-'.$section)
            ->with('saved_section', $section);
    }
}
