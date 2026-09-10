<?php

declare(strict_types=1);

namespace Monkward\Config;

final class ConfigInstaller
{
    /**
     * Materializes monkward's configuration in ~/.config/monkward (or
     * $XDG_CONFIG_HOME/monkward): a config.toml with the prebaked defaults and
     * a copy of the default theme so it can be edited. Existing files are
     * never overwritten.
     *
     * @return list<string> paths that were created
     */
    public function ensureInstalled(): array
    {
        $configDir = UserConfig::configDir();
        if ($configDir === null) {
            return [];
        }

        $created = [];
        $themesDir = "{$configDir}/themes";

        if (! \is_dir($configDir)) {
            $created[] = $configDir;
        }
        if (! \is_dir($themesDir)) {
            $created[] = $themesDir;
        }
        if (! \is_dir($themesDir) && ! \mkdir($themesDir, 0755, true) && ! \is_dir($themesDir)) {
            throw new \RuntimeException("could not create {$themesDir}");
        }

        $configFile = "{$configDir}/config.toml";
        if (! \is_file($configFile)) {
            if (@\file_put_contents($configFile, $this->defaultConfigToml()) === false) {
                throw new \RuntimeException("could not write {$configFile}");
            }
            $created[] = $configFile;
        }

        $userTheme = "{$themesDir}/default.css";
        if (! \is_file($userTheme)) {
            $builtIn = ThemeManager::builtInDefaultThemePath();
            $css = @\file_get_contents($builtIn);
            if ($css === false || @\file_put_contents($userTheme, $css) === false) {
                throw new \RuntimeException("could not install default theme to {$userTheme}");
            }
            $created[] = $userTheme;
        }

        return $created;
    }

    public function defaultConfigToml(): string
    {
        return <<<TOML
# monkward configuration
# Edit these defaults to taste; run `monkward --help` for details.

theme = "default"
port = 8800
host = "127.0.0.1"

# Add id="..." attributes to rendered headings so [links](#anchors) work.
# Set to false to leave headings exactly as written.
heading_ids = true
TOML;
    }
}
