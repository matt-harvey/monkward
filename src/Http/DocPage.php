<?php

declare(strict_types=1);

namespace Monkward\Http;

use Monkward\Markdown\MarkdownRenderer;
use SubstancePHP\HTTP\Exception\BaseException\UserError;

final class DocPage
{
    public function __construct(
        private string $root,
        private ?string $singleFile,
        private MarkdownRenderer $markdown,
    ) {
    }

    /** @return array{title: string, rel: string, dir: string, html: string} */
    public function render(string $relative): array
    {
        $relative = \str_replace('\\', '/', $relative);

        if (! \str_ends_with(\strtolower($relative), '.md')) {
            UserError::throw(404);
        }

        if ($this->singleFile !== null && $relative !== \basename($this->singleFile)) {
            UserError::throw(404);
        }

        $absolute = $this->resolve($relative);
        $markdown = @\file_get_contents($absolute);
        if ($markdown === false) {
            UserError::throw(404);
        }

        $dir = \dirname($relative);

        return [
            'title' => \preg_replace('/\.md$/i', '', \basename($relative)) ?? \basename($relative),
            'rel' => $relative,
            'dir' => $dir === '.' ? '' : $dir,
            'html' => $this->markdown->render($markdown),
        ];
    }

    private function resolve(string $relative): string
    {
        $rootReal = \realpath($this->root) ?: \rtrim($this->root, '/\\');
        $candidate = \rtrim($rootReal, '/\\') . '/' . $relative;
        $real = \realpath($candidate);

        if ($real === false || ! \is_file($real)) {
            UserError::throw(404);
        }

        $prefix = \rtrim($rootReal, '/\\') . '/';
        if (! \str_starts_with($real, $prefix)) {
            UserError::throw(404);
        }

        return $real;
    }
}
