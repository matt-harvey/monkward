<?php

declare(strict_types=1);

namespace Monkward\Http;

use Monkward\Config\ThemeRegistry;
use SubstancePHP\HTTP\Exception\BaseException\UserError;
use SubstancePHP\HTTP\RequestParams\QueryParams;
use SubstancePHP\HTTP\Respond;

final class AssetResponder
{
    public function __construct(
        private ThemeRegistry $themes,
        private string $defaultTheme,
    ) {}

    public function isAsset(string $file): bool
    {
        return $file === 'monkward-theme.css'
            || $file === 'monkward-themes.json'
            || $file === 'favicon.svg';
    }

    public function respond(string $file, Respond $respond, QueryParams $query): mixed
    {
        if ($file === 'monkward-theme.css') {
            return $this->respondThemeCss($respond, $query);
        }

        if ($file === 'monkward-themes.json') {
            return $this->respondThemeList($respond);
        }

        if ($file === 'favicon.svg') {
            return $respond(200, $this->favicon(), 'image/svg+xml; charset=utf-8');
        }

        UserError::throw(404);
    }

    private function favicon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">'
            . '<rect width="32" height="32" rx="7" fill="#0f766e"/>'
            . '<text x="16" y="22.5" font-family="ui-monospace,monospace" font-size="17" '
            . 'font-weight="700" text-anchor="middle" fill="#f0fdfa">m</text>'
            . '</svg>';
    }

    private function respondThemeCss(Respond $respond, QueryParams $query): mixed
    {
        $name = (string) ($query['theme'] ?? $this->defaultTheme);
        $path = $this->themes->resolvePath($name);

        if ($path === null) {
            UserError::throw(404, "theme '{$name}' not found");
        }

        $css = @\file_get_contents($path);
        if ($css === false) {
            UserError::throw(500, 'Unable to read theme stylesheet');
        }

        return $respond(200, $css, 'text/css; charset=utf-8');
    }

    private function respondThemeList(Respond $respond): mixed
    {
        $data = \json_encode([
            'default' => $this->defaultTheme,
            'themes' => $this->themes->availableThemes(),
        ], \JSON_THROW_ON_ERROR);

        return $respond(200, $data, 'application/json');
    }
}
