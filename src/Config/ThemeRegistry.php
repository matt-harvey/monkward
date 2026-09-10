<?php

declare(strict_types=1);

namespace Monkward\Config;

use Monkward\MonkwardException;

final class ThemeRegistry
{
    /** @return list<string> theme names available (built-in plus user-installed), sorted */
    public function availableThemes(): array
    {
        $names = [];

        foreach (self::builtInThemes() as $name) {
            $names[$name] = true;
        }

        $userDir = $this->userThemesDir();
        if ($userDir !== null && \is_dir($userDir)) {
            $entries = @\scandir($userDir);
            if ($entries !== false) {
                foreach ($entries as $entry) {
                    if (\str_ends_with(\strtolower($entry), '.css')) {
                        $names[\substr($entry, 0, -4)] = true;
                    }
                }
            }
        }

        $list = \array_keys($names);
        \usort($list, static fn (string $a, string $b): int => \strnatcasecmp($a, $b));

        return $list;
    }

    /** Returns the CSS file to serve for a theme, preferring the user's copy. */
    public function resolvePath(string $name): ?string
    {
        if (! \preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
            return null;
        }

        $userDir = $this->userThemesDir();
        if ($userDir !== null) {
            $userPath = $userDir . '/' . $name . '.css';
            if (\is_file($userPath)) {
                return $userPath;
            }
        }

        $builtIn = self::builtInThemesDir() . '/' . $name . '.css';
        if (\is_file($builtIn)) {
            return $builtIn;
        }

        return null;
    }

    /** @return list<string> */
    public static function builtInThemes(): array
    {
        $dir = self::builtInThemesDir();
        if (! \is_dir($dir)) {
            return [];
        }

        $names = [];
        $entries = @\scandir($dir);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if (\str_ends_with(\strtolower($entry), '.css')) {
                $names[] = \substr($entry, 0, -4);
            }
        }

        \usort($names, static fn (string $a, string $b): int => \strnatcasecmp($a, $b));

        return $names;
    }

    public static function builtInThemesDir(): string
    {
        return \dirname(__DIR__, 2) . '/resources/themes';
    }

    private function userThemesDir(): ?string
    {
        try {
            return UserConfig::themesDir();
        } catch (MonkwardException) {
            return null;
        }
    }
}
