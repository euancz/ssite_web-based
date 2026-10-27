<?php

namespace App\Support;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class ArticleImageRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        // Inline image URLs never load remote content; authors use the validated upload control.
        return $childRenderer->renderNodes($node->children());
    }
}
