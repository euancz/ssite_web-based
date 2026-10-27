<?php

namespace App\Http\Requests;

use App\Rules\ConsecutiveAcademicYear;
use App\Services\ArticleContent;
use App\Support\ContentModules;
use App\Support\DocumentAcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveContentDraftRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['image', 'file'] as $field) {
            if ($this->has($field) && ! $this->file($field)) {
                $this->request->remove($field);
            }
        }
    }

    public function authorize(): bool
    {
        $module = ContentModules::all()[$this->route('module')] ?? null;

        return $module && $this->user()?->can('create', $module['model']);
    }

    public function rules(): array
    {
        $rules = [
            'draft_id' => ['nullable', 'uuid'],
            'draft_version' => ['required_with:draft_id', 'nullable', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:200'],
        ];
        $body = $this->route('module') === 'articles' ? 'content' : 'description';
        // Drafts may exceed the publication word limit while the author is still writing.
        $rules[$body] = ['nullable', 'string', 'max:20000'];
        $rules['image'] = in_array($this->route('module'), ['articles', 'activities', 'achievements'], true)
            ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'] : ['exclude'];
        $rules['file'] = in_array($this->route('module'), ['documents', 'liquidations'], true)
            ? ['nullable', 'file', 'mimes:pdf', 'max:'.config('school.max_pdf_size_kb', 10240)] : ['exclude'];

        return $rules + match ($this->route('module')) {
            'articles' => ['content_format' => app(ArticleContent::class)->available()
                ? ['sometimes', 'required', Rule::in(['plain', 'markdown'])] : ['exclude'],
                'category' => \App\Support\ArticleCategory::available() ? ['nullable', 'string', 'max:100'] : ['exclude']],
            'activities' => ['activity_date' => ['nullable', 'date_format:Y-m-d'], 'location' => ['nullable', 'string', 'max:200']],
            'achievements' => ['achievement_date' => ['nullable', 'date_format:Y-m-d'], 'awardee' => ['nullable', 'string', 'max:200'], 'category' => ['nullable', 'string', 'max:100']],
            'documents' => ['category' => ['nullable', 'string', 'max:100'], 'semester' => ['nullable', Rule::in(['1', '2'])],
                'academic_year' => DocumentAcademicYear::available()
                    ? ['nullable', 'string', 'max:9', new ConsecutiveAcademicYear] : ['exclude']],
            'liquidations' => ['report_date' => ['nullable', 'date_format:Y-m-d'], 'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99']],
            default => [],
        };
    }
}
