<?php

declare(strict_types=1);

namespace Monkward\Site;

final class DirectoryLister
{
    public function __construct(
        private string $root,
    ) {}

    public function resolveDir(string $relativeDir): ?string
    {
        $rootReal = \realpath($this->root) ?: \rtrim($this->root, '/\\');
        $candidate = $relativeDir === ''
            ? $rootReal
            : \rtrim($rootReal, '/\\') . '/' . $relativeDir;
        $real = \realpath($candidate);

        if ($real === false || ! \is_dir($real)) {
            return null;
        }
        if (! $this->isWithinRoot($rootReal, $real)) {
            return null;
        }

        return $real;
    }

    public function resolveFile(string $relativeFile): ?string
    {
        $rootReal = \realpath($this->root) ?: \rtrim($this->root, '/\\');
        $candidate = \rtrim($rootReal, '/\\') . '/' . $relativeFile;
        $real = \realpath($candidate);

        if ($real === false || ! \is_file($real)) {
            return null;
        }
        if (! $this->isWithinRoot($rootReal, $real)) {
            return null;
        }

        return $real;
    }

    /**
     * Lists one level of a directory: directories first, then markdown files,
     * then everything else.
     *
     * @return array{
     *     dirs: list<array{name: string, href: string}>,
     *     files: list<array{name: string, md: bool, href: ?string}>
     * }
     */
    public function list(string $relativeDir): array
    {
        $abs = $this->resolveDir($relativeDir);
        if ($abs === null) {
            throw new \InvalidArgumentException("not a directory: {$relativeDir}");
        }

        $dirs = [];
        $files = [];
        $entries = @\scandir($abs);
        if ($entries === false) {
            throw new \InvalidArgumentException("could not read directory: {$relativeDir}");
        }

        foreach ($entries as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $path = \rtrim($abs, '/\\') . \DIRECTORY_SEPARATOR . $name;
            $rel = $relativeDir === '' ? $name : $relativeDir . '/' . $name;

            if (\is_dir($path)) {
                $dirs[] = ['name' => $name, 'href' => '/' . self::encodePath($rel) . '/'];
                continue;
            }

            if (\is_file($path) || \is_link($path)) {
                $md = \strtolower(\pathinfo($name, \PATHINFO_EXTENSION)) === 'md';
                $files[] = [
                    'name' => $name,
                    'md' => $md,
                    'href' => $md ? '/' . self::encodePath($rel) : null,
                ];
            }
        }

        \usort($dirs, static fn(array $a, array $b): int => \strnatcasecmp($a['name'], $b['name']));
        \usort($files, static function (array $a, array $b): int {
            if ($a['md'] !== $b['md']) {
                return $a['md'] ? -1 : 1;
            }
            return \strnatcasecmp($a['name'], $b['name']);
        });

        return ['dirs' => $dirs, 'files' => $files];
    }

    public static function encodePath(string $path): string
    {
        return \implode('/', \array_map('rawurlencode', \explode('/', \str_replace('\\', '/', $path))));
    }

    private function isWithinRoot(string $rootReal, string $real): bool
    {
        $rootReal = \rtrim($rootReal, '/\\');

        return $real === $rootReal || \str_starts_with($real, $rootReal . '/');
    }
}
