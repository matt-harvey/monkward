<?php

declare(strict_types=1);

namespace Monkward\Http;

use Monkward\Markdown\MarkdownRenderer;
use Monkward\Site\Breadcrumb;
use Monkward\Site\DirectoryLister;
use SubstancePHP\HTTP\Exception\BaseException\UserError;

final class DocPage
{
    public function __construct(
        private ?string $singleFile,
        private MarkdownRenderer $markdown,
        private DirectoryLister $lister,
    ) {}

    /** @return array{title: string, rel: string, html: string, crumbs: list<Breadcrumb>} */
    public function render(string $relative): array
    {
        $relative = \str_replace('\\', '/', $relative);

        if (! \str_ends_with(\strtolower($relative), '.md')) {
            UserError::throw(404);
        }

        if ($this->singleFile !== null && $relative !== \basename($this->singleFile)) {
            UserError::throw(404);
        }

        $absolute = $this->lister->resolveFile($relative);
        if ($absolute === null) {
            UserError::throw(404);
        }

        $markdown = @\file_get_contents($absolute);
        if ($markdown === false) {
            UserError::throw(404);
        }

        return [
            'title' => \preg_replace('/\.md$/i', '', \basename($relative)) ?? \basename($relative),
            'rel' => $relative,
            'html' => $this->markdown->render($markdown),
            'crumbs' => $this->breadcrumbs($relative),
        ];
    }

    /** @return list<Breadcrumb> */
    private function breadcrumbs(string $relative): array
    {
        $dir = \dirname($relative);
        if ($dir === '.') {
            return [];
        }

        $crumbs = [];
        $prefix = '';
        foreach (\explode('/', $dir) as $part) {
            $prefix = $prefix === '' ? $part : $prefix . '/' . $part;
            $crumbs[] = new Breadcrumb($part, '/' . DirectoryLister::encodePath($prefix) . '/');
        }

        return $crumbs;
    }
}
