<?php

declare(strict_types=1);

namespace Monkward\Http;

use SubstancePHP\HTTP\RendererInterface;

final class PlainTextRenderer implements RendererInterface
{
    public function __construct(
        private string $content,
    ) {}

    #[\Override]
    public function render(): string
    {
        return $this->content;
    }
}
