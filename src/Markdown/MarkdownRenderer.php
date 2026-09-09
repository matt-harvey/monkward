<?php

declare(strict_types=1);

namespace Monkward\Markdown;

use League\CommonMark\GithubFlavoredMarkdownConverter;

final class MarkdownRenderer
{
    private GithubFlavoredMarkdownConverter $converter;

    public function __construct(?GithubFlavoredMarkdownConverter $converter = null)
    {
        $this->converter = $converter ?? new GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);
    }

    public function render(string $markdown): string
    {
        return (string) $this->converter->convert($markdown);
    }
}
