<?php

declare(strict_types=1);

namespace Monkward\Site;

final readonly class DirectoryListing
{
    /**
     * @param list<DirEntry> $dirs
     * @param list<FileEntry> $files
     */
    public function __construct(
        public array $dirs,
        public array $files,
    ) {}
}
