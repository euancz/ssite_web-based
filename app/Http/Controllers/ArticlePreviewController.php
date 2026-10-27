<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\ArticleContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ArticlePreviewController extends Controller
{
    public function __invoke(Request $request, ArticleContent $content): JsonResponse
    {
        Gate::authorize('create', Article::class);
        abort_unless($content->available(), 404);
        $values = $request->validate([
            'content' => ['nullable', 'string', 'max:20000'],
            'content_format' => ['required', Rule::in(['plain', 'markdown'])],
        ]);

        return response()->json(['html' => $content->html($values['content'] ?? '', $values['content_format'])])
            ->header('Cache-Control', 'no-store, private');
    }
}
