<?php

namespace App\Services;

use App\Support\ArticleImageRenderer;
use App\Support\ArticleLinkRenderer;
use Illuminate\Support\Facades\Schema;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;

class ArticleContent
{
    private ?CommonMarkConverter $converter = null;

    public function available(): bool
    {
        return $this->availableFor('articles');
    }

    public function availableFor(string $table): bool
    {
        return in_array($table, ['articles', 'activities', 'achievements'], true)
            && Schema::hasColumn($table, 'content_format');
    }

    public function html(string $source, string $format = 'plain'): string
    {
        if ($format !== 'markdown') {
            return '<p>'.nl2br(e($source)).'</p>';
        }
        if (! $this->converter) {
            $this->converter = new CommonMarkConverter([
                'html_input' => 'escape', 'allow_unsafe_links' => false,
                'max_nesting_level' => 20, 'max_delimiters_per_line' => 1000,
            ]);
            $this->converter->getEnvironment()->addExtension(new StrikethroughExtension);
            $this->converter->getEnvironment()->addExtension(new TableExtension);
            $this->converter->getEnvironment()->addRenderer(Link::class, new ArticleLinkRenderer, 100);
            $this->converter->getEnvironment()->addRenderer(Image::class, new ArticleImageRenderer, 100);
        }

        return (string) $this->converter->convert(mb_substr($source, 0, 20000));
    }

    public function text(string $source, string $format = 'plain'): string
    {
        if ($format !== 'markdown') {
            return $source;
        }

        return trim(html_entity_decode(strip_tags($this->html($source, $format)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
