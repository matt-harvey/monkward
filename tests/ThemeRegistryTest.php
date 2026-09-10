<?php

declare(strict_types=1);

namespace Monkward\Tests;

use Monkward\Config\ThemeRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ThemeRegistryTest extends TestCase
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
    public function builtInThemesAreAlwaysAvailable(): void
    {
        $registry = new ThemeRegistry();

        self::assertContains('light', $registry->availableThemes());
        self::assertContains('dark', $registry->availableThemes());
        self::assertNotNull($registry->resolvePath('light'));
        self::assertNotNull($registry->resolvePath('dark'));
    }

    #[Test]
    public function userThemesAreListedAndPreferred(): void
    {
        \file_put_contents($this->configHome . '/monkward/themes/yeah.css', 'body { color: red; }');

        $registry = new ThemeRegistry();

        self::assertContains('yeah', $registry->availableThemes());
        self::assertStringContainsString('body { color: red; }', (string) \file_get_contents($registry->resolvePath('yeah')));
    }

    #[Test]
    public function userCopyOfBuiltInThemeWins(): void
    {
        $userDark = $this->configHome . '/monkward/themes/dark.css';
        \file_put_contents($userDark, 'body { background: hotpink; }');

        $registry = new ThemeRegistry();

        self::assertSame($userDark, $registry->resolvePath('dark'));
    }

    #[Test]
    public function unknownAndUnsafeNamesResolveToNull(): void
    {
        $registry = new ThemeRegistry();

        self::assertNull($registry->resolvePath('nope'));
        self::assertNull($registry->resolvePath('../evil'));
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
