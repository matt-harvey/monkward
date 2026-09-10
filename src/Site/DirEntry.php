<?php

declare(strict_types=1);

namespace Monkward\Site;

final readonly class DirEntry
{
    public function __construct(
        public string $name,
        public string $href,
    ) {}
}
