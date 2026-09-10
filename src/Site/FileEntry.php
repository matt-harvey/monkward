<?php

declare(strict_types=1);

namespace Monkward\Site;

final readonly class FileEntry
{
    public function __construct(
        public string $name,
        public bool $md,
        public ?string $href,
    ) {}
}
