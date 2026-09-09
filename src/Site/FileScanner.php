<?php

declare(strict_types=1);

namespace Monkward\Site;

final class FileScanner
{
    /** @var list<string> lowercase directory names that are ignored */
    private array $ignoredDirs;

    /** @param list<string> $ignore directory names to ignore (case-insensitive) */
    public function __construct(array $ignore = [])
    {
        $this->ignoredDirs = \array_values(\array_map('strtolower', $ignore));
    }

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
                fn (\SplFileInfo $current): bool => $this->accept($current),
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

    /** Whether a file path sits under an ignored directory (ignores the file name itself). */
    public function isIgnored(string $relativePath): bool
    {
        $parts = \explode('/', \str_replace('\\', '/', $relativePath));
        \array_pop($parts); // the last segment is the file name; ignore applies to directories

        foreach ($parts as $part) {
            if (\in_array(\strtolower($part), $this->ignoredDirs, true)) {
                return true;
            }
        }

        return false;
    }

    private function accept(\SplFileInfo $current): bool
    {
        $name = $current->getFilename();

        if ($current->isDir()) {
            return ! \in_array(\strtolower($name), $this->ignoredDirs, true);
        }

        return $current->isFile() && ! \str_starts_with($name, '.');
    }
}
