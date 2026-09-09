<?php

declare(strict_types=1);

namespace Monkward\Http;

use SubstancePHP\HTTP\RendererFactory;
use SubstancePHP\HTTP\RendererFactoryInterface;
use SubstancePHP\HTTP\RendererInterface;

/**
 * Maps monkward's tiny route surface onto the substancephp/http templating engine:
 * the root route renders `index.html.php`, every document route renders `doc.html.php`,
 * error templates pass through untouched, and non-HTML payloads (theme css, favicon)
 * are emitted verbatim.
 */
final class MonkwardRendererFactory implements RendererFactoryInterface
{
    public function __construct(
        private RendererFactory $inner,
    ) {
    }

    #[\Override]
    public function createRenderer(
        string $normalizedRequestPath,
        string $responseContentType,
        mixed $responseData,
    ): RendererInterface {
        if (\str_starts_with($responseContentType, 'text/css')
            || \str_starts_with($responseContentType, 'image/svg+xml')
        ) {
            return new PlainTextRenderer((string) $responseData);
        }

        $templatePath = match (true) {
            $normalizedRequestPath === 'index' => 'index',
            \str_starts_with($normalizedRequestPath, 'error') => $normalizedRequestPath,
            default => 'doc',
        };

        return $this->inner->createRenderer($templatePath, $responseContentType, $responseData);
    }
}
