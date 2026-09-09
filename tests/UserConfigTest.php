<?php

declare(strict_types=1);

namespace Monkward\Tests;

use Monkward\Config\UserConfig;
use Monkward\MonkwardException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserConfigTest extends TestCase
{
    #[Test]
    public function parsesThemePortHost(): void
    {
        $config = UserConfig::fromToml(<<<'TOML'
# monkward config
theme = "yeah"  # a comment
port = 9100
host = '0.0.0.0'
TOML);

        self::assertSame('yeah', $config->theme);
        self::assertSame(9100, $config->port);
        self::assertSame('0.0.0.0', $config->host);
    }

    #[Test]
    public function emptyTomlYieldsNulls(): void
    {
        $config = UserConfig::fromToml('');

        self::assertNull($config->theme);
        self::assertNull($config->port);
        self::assertNull($config->host);
    }

    #[Test]
    public function parsesIgnoreArray(): void
    {
        $config = UserConfig::fromToml(<<<'TOML'
ignore = ["build", "tmp"]
TOML);

        self::assertSame(['build', 'tmp'], $config->ignore);
    }

    #[Test]
    public function missingIgnoreKeyFallsBackToPrebakedList(): void
    {
        $config = UserConfig::fromToml('theme = "solar"');

        self::assertSame(UserConfig::DEFAULT_IGNORE, $config->ignore);
    }

    #[Test]
    public function explicitEmptyIgnoreListIsRespected(): void
    {
        $config = UserConfig::fromToml('ignore = []');

        self::assertSame([], $config->ignore);
    }

    #[Test]
    public function unknownKeysAreIgnored(): void
    {
        $config = UserConfig::fromToml("theme = \"solar\"\nverbosity = 3\n[section]\nfoo = \"bar\"\n");

        self::assertSame('solar', $config->theme);
    }

    #[Test]
    public function invalidPortIsRejected(): void
    {
        $this->expectException(MonkwardException::class);

        UserConfig::fromToml('port = 99999');
    }
}
