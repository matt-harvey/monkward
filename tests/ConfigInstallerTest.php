<?php

declare(strict_types=1);

namespace Monkward\Tests;

use Monkward\Config\ConfigInstaller;
use Monkward\Config\UserConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigInstallerTest extends TestCase
{
    private string $configHome = '';

    protected function setUp(): void
    {
        $this->configHome = \sys_get_temp_dir() . '/monkward-installer-test-' . \bin2hex(\random_bytes(6));
        \putenv('XDG_CONFIG_HOME=' . $this->configHome);
    }

    protected function tearDown(): void
    {
        \putenv('XDG_CONFIG_HOME');
        $this->removeTree($this->configHome);
    }

    #[Test]
    public function ensureInstalledCreatesConfigAndBuiltInThemes(): void
    {
        $created = (new ConfigInstaller())->ensureInstalled();

        $configDir = UserConfig::configDir();
        self::assertNotNull($configDir);
        self::assertContains($configDir, $created);
        self::assertContains($configDir . '/config.toml', $created);
        self::assertContains($configDir . '/themes/light.css', $created);
        self::assertContains($configDir . '/themes/dark.css', $created);

        self::assertFileExists($configDir . '/config.toml');
        self::assertFileExists($configDir . '/themes/light.css');
        self::assertFileExists($configDir . '/themes/dark.css');
    }

    #[Test]
    public function ensureInstalledIsIdempotentAndDoesNotOverwrite(): void
    {
        (new ConfigInstaller())->ensureInstalled();

        $configDir = UserConfig::configDir();
        self::assertNotNull($configDir);

        \file_put_contents($configDir . '/config.toml', 'theme = "mine"');

        $created = (new ConfigInstaller())->ensureInstalled();

        self::assertSame([], $created);
        self::assertSame('theme = "mine"', \file_get_contents($configDir . '/config.toml'));
    }

    #[Test]
    public function prebakedConfigContainsTheDefaults(): void
    {
        $toml = (new ConfigInstaller())->defaultConfigToml();

        self::assertStringContainsString('theme = "light"', $toml);
        self::assertStringContainsString('heading_ids = true', $toml);

        $parsed = UserConfig::fromToml($toml);
        self::assertTrue($parsed->headingIds);
        self::assertSame('light', $parsed->theme);
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
