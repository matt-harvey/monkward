<?php

declare(strict_types=1);

namespace Monkward\Tests;

use Monkward\Markdown\MarkdownRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MarkdownRendererTest extends TestCase
{
    #[Test]
    public function rendersGithubFlavoredMarkdown(): void
    {
        $renderer = new MarkdownRenderer();

        $html = $renderer->render(<<<'MD'
# Title

| a | b |
|---|---|
| 1 | 2 |

~~gone~~ and **bold**.
MD);

        self::assertStringContainsString('<h1>Title</h1>', $html);
        self::assertStringContainsString('<table>', $html);
        self::assertStringContainsString('<del>gone</del>', $html);
        self::assertStringContainsString('<strong>bold</strong>', $html);
    }

    #[Test]
    public function allowsInlineHtmlByDefault(): void
    {
        $html = (new MarkdownRenderer())->render("<div class=\"x\">hi</div>\n");

        self::assertStringContainsString('<div class="x">hi</div>', $html);
    }
}
