<?php

declare(strict_types=1);

namespace Monkward\Site;

final class FileScanner
{
    /** @return list<string> relative slash-separated paths of all .md files under $root */
    public function scan(string $root): array
    {
        $root = \rtrim($root, '/\\');
        if (! \is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator(
                    $root,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO,
                ),
                static fn (\SplFileInfo $current): bool => self::accept($current),
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY,
            \RecursiveIteratorIterator::CATCH_GET_CHILD,
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }
            $extension = \strtolower(\pathinfo($fileInfo->getFilename(), \PATHINFO_EXTENSION));
            if ($extension !== 'md') {
                continue;
            }
            $relative = \substr($fileInfo->getPathname(), \strlen($root) + 1);
            $files[] = \str_replace(\DIRECTORY_SEPARATOR, '/', $relative);
        }

        \usort($files, static fn (string $a, string $b): int => \strnatcasecmp($a, $b));

        return $files;
    }

    private static function accept(\SplFileInfo $current): bool
    {
        $name = $current->getFilename();

        if (\str_starts_with($name, '.')) {
            return false;
        }

        return $current->isDir() || $current->isFile();
    }
}
