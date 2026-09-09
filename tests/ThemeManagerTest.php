<?php

declare(strict_types=1);

namespace Monkward\Tests;

use Monkward\Config\ThemeManager;
use Monkward\MonkwardException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ThemeManagerTest extends TestCase
{
    private string $configHome = '';

    protected function setUp(): void
    {
        $this->configHome = \sys_get_temp_dir() . '/monkward-theme-test-' . \bin2hex(\random_bytes(6));
        \mkdir($this->configHome . '/monkward/themes', 0777, true);
        \putenv('XDG_CONFIG_HOME=' . $this->configHome);
    }

    protected function tearDown(): void
    {
        \putenv('XDG_CONFIG_HOME');
        $this->removeTree($this->configHome);
    }

    #[Test]
    public function resolvesDefaultThemeWhenNoNameGiven(): void
    {
        $theme = (new ThemeManager())->resolve(null, false);

        self::assertSame('default', $theme->name);
        self::assertFileExists($theme->path);
    }

    #[Test]
    public function defaultThemePrefersTheInstalledUserCopy(): void
    {
        $userDefault = $this->configHome . '/monkward/themes/default.css';
        \file_put_contents($userDefault, 'body { background: hotpink; }');

        $theme = (new ThemeManager())->resolve('default', false);

        self::assertSame($userDefault, $theme->path);
        self::assertStringContainsString('hotpink', (string) \file_get_contents($theme->path));
    }

    #[Test]
    public function resolvesUserTheme(): void
    {
        \file_put_contents($this->configHome . '/monkward/themes/yeah.css', 'body { color: red; }');

        $theme = (new ThemeManager())->resolve('yeah', false);

        self::assertSame('yeah', $theme->name);
        self::assertStringContainsString('body { color: red; }', (string) \file_get_contents($theme->path));
    }

    #[Test]
    public function strictMissingThemeThrows(): void
    {
        $this->expectException(MonkwardException::class);
        $this->expectExceptionMessage("theme 'nope' not found");

        (new ThemeManager())->resolve('nope', true);
    }

    #[Test]
    public function lenientMissingThemeFallsBackToDefault(): void
    {
        $theme = (new ThemeManager())->resolve('nope', false);

        self::assertSame('default', $theme->name);
    }

    #[Test]
    public function rejectsUnsafeThemeNames(): void
    {
        $this->expectException(MonkwardException::class);

        (new ThemeManager())->resolve('../evil', true);
    }

    private function removeTree(string $dir): void
    {
        if (! \is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() ? @\rmdir($item->getPathname()) : @\unlink($item->getPathname());
        }
        @\rmdir($dir);
    }
}
