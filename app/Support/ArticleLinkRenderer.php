<?php

namespace App\Support;

use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class ArticleLinkRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string|\Stringable
    {
        Link::assertInstanceOf($node);
        $label = $childRenderer->renderNodes($node->children());
        $url = $node->getUrl();
        // Only absolute HTTP(S) destinations; no scripts, data, file, or protocol-relative URLs.
        if (! preg_match('~^https?://[^\s\\\\]+$~i', $url) || ! parse_url($url, PHP_URL_HOST)) {
            return $label;
        }

        return new HtmlElement('a', ['href' => $url, 'rel' => 'nofollow ugc noreferrer'], $label);
    }
}
