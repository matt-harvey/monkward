<?php

declare(strict_types=1);

namespace Monkward\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

final class MarkdownRenderer
{
    private MarkdownConverter $converter;

    public function __construct(
        private bool $headingIds = true,
        ?MarkdownConverter $converter = null,
    ) {
        $this->converter = $converter ?? self::createConverter($this->headingIds);
    }

    public function render(string $markdown): string
    {
        return (string) $this->converter->convert($markdown);
    }

    private static function createConverter(bool $headingIds): MarkdownConverter
    {
        $config = [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ];

        if ($headingIds) {
            $config['heading_permalink'] = [
                'insert' => 'none',
                'apply_id_to_heading' => true,
                'id_prefix' => '',
                'fragment_prefix' => '',
            ];
        }

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        if ($headingIds) {
            $environment->addExtension(new HeadingPermalinkExtension());
        }

        return new MarkdownConverter($environment);
    }
}
