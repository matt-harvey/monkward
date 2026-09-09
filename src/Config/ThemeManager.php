<?php

declare(strict_types=1);

namespace Monkward\Config;

use Monkward\MonkwardException;

final class ThemeManager
{
    public function resolve(?string $name, bool $strict): Theme
    {
        $name ??= UserConfig::DEFAULT_THEME;

        if ($name === '' || $name === UserConfig::DEFAULT_THEME) {
            return $this->resolveDefault();
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
        return $this->resolveDefault();
    }

    public static function builtInDefaultThemePath(): string
    {
        return \dirname(__DIR__, 2) . '/resources/themes/default.css';
    }

    private function resolveDefault(): Theme
    {
        $userTheme = UserConfig::themesDir() . '/' . UserConfig::DEFAULT_THEME . '.css';

        if (\is_file($userTheme)) {
            return new Theme(UserConfig::DEFAULT_THEME, $userTheme);
        }

        return new Theme(UserConfig::DEFAULT_THEME, self::builtInDefaultThemePath());
    }

    private function warn(string $message): void
    {
        \fwrite(\STDERR, "monkward: warning: {$message}\n");
    }
}
