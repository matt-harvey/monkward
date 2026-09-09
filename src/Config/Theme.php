<?php

declare(strict_types=1);

namespace Monkward\Config;

final readonly class Theme
{
    public function __construct(
        public string $name,
        public string $path,
    ) {
    }
}
