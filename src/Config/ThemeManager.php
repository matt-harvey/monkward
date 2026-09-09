<?php

declare(strict_types=1);

namespace Monkward\Config;

use Monkward\MonkwardException;

final class ThemeManager
{
    public function resolve(?string $name, bool $strict): Theme
    {
        $default = new Theme('default', $this->projectRoot() . '/resources/themes/default.css');

        if ($name === null || $name === '' || $name === 'default') {
            return $default;
        }

        if (! \preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
            throw new MonkwardException("invalid theme name: {$name}");
        }

        $path = UserConfig::themesDir() . '/' . $name . '.css';

        if (\is_file($path)) {
            return new Theme($name, $path);
        }

        if ($strict) {
            throw new MonkwardException("theme '{$name}' not found (looked for {$path})");
        }

        $this->warn("theme '{$name}' not found; falling back to the default theme");
        return $default;
    }

    private function projectRoot(): string
    {
        return \dirname(__DIR__, 2);
    }

    private function warn(string $message): void
    {
        \fwrite(\STDERR, "monkward: warning: {$message}\n");
    }
}
