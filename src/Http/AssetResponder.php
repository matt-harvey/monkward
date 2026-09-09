<?php

declare(strict_types=1);

namespace Monkward\Http;

use SubstancePHP\HTTP\Exception\BaseException\UserError;
use SubstancePHP\HTTP\Respond;

final class AssetResponder
{
    private const FAVICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="7" fill="#0f766e"/><text x="16" y="22.5" font-family="ui-monospace,monospace" font-size="17" font-weight="700" text-anchor="middle" fill="#f0fdfa">m</text></svg>';

    public function __construct(
        private string $themeCssPath,
    ) {
    }

    public function isAsset(string $file): bool
    {
        return $file === 'monkward-theme.css' || $file === 'favicon.svg';
    }

    public function respond(string $file, Respond $respond): mixed
    {
        if ($file === 'monkward-theme.css') {
            $css = $this->themeCssPath === '' ? '' : @\file_get_contents($this->themeCssPath);
            if ($css === false) {
                UserError::throw(500, 'Unable to read theme stylesheet');
            }
            return $respond(200, $css, 'text/css; charset=utf-8');
        }

        if ($file === 'favicon.svg') {
            return $respond(200, self::FAVICON, 'image/svg+xml; charset=utf-8');
        }

        UserError::throw(404);
    }
}
