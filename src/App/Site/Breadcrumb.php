<?php

declare(strict_types=1);

namespace Monkward\Site;

final readonly class Breadcrumb
{
    public function __construct(
        public string $label,
        public string $href,
    ) {}
}
