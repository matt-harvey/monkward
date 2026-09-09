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

        self::assertStringContainsString('<h1 id="title">Title</h1>', $html);
        self::assertStringContainsString('<table>', $html);
        self::assertStringContainsString('<del>gone</del>', $html);
        self::assertStringContainsString('<strong>bold</strong>', $html);
    }

    #[Test]
    public function addsIdsToHeadingsSoAnchorLinksWork(): void
    {
        $html = (new MarkdownRenderer())->render("# Section One\n\n[go](#section-one)\n");

        self::assertStringContainsString('<h1 id="section-one">Section One</h1>', $html);
        self::assertStringContainsString('<a href="#section-one">go</a>', $html);
    }

    #[Test]
    public function headingIdsCanBeDisabled(): void
    {
        $html = (new MarkdownRenderer(headingIds: false))->render("# Section One\n");

        self::assertStringContainsString('<h1>Section One</h1>', $html);
        self::assertStringNotContainsString('id="section-one"', $html);
    }

    #[Test]
    public function allowsInlineHtmlByDefault(): void
    {
        $html = (new MarkdownRenderer())->render("<div class=\"x\">hi</div>\n");

        self::assertStringContainsString('<div class="x">hi</div>', $html);
    }
}
