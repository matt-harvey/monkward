<?php

declare(strict_types=1);

namespace Monkward\Tests;

use Monkward\Console\Arguments;
use Monkward\MonkwardException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ArgumentsTest extends TestCase
{
    #[Test]
    public function parsesFlagsAndPath(): void
    {
        $args = Arguments::parse(['monkward', '--theme=yeah', '--port', '9000', '--host=0.0.0.0', '--no-browser', 'docs']);

        self::assertSame('yeah', $args->theme);
        self::assertSame(9000, $args->port);
        self::assertSame('0.0.0.0', $args->host);
        self::assertTrue($args->noBrowser);
        self::assertSame('docs', $args->path);
    }

    #[Test]
    public function defaultsAreNull(): void
    {
        $args = Arguments::parse(['monkward']);

        self::assertNull($args->theme);
        self::assertNull($args->port);
        self::assertNull($args->host);
        self::assertFalse($args->noBrowser);
        self::assertNull($args->path);
    }

    #[Test]
    public function helpAndVersionShortFlags(): void
    {
        self::assertTrue(Arguments::parse(['monkward', '-h'])->help);
        self::assertTrue(Arguments::parse(['monkward', '--help'])->help);
        self::assertTrue(Arguments::parse(['monkward', '-V'])->version);
        self::assertTrue(Arguments::parse(['monkward', '--version'])->version);
    }

    #[Test]
    public function rejectsUnknownOptions(): void
    {
        $this->expectException(MonkwardException::class);
        $this->expectExceptionMessage('unknown option');

        Arguments::parse(['monkward', '--wat']);
    }

    #[Test]
    public function rejectsMoreThanOnePath(): void
    {
        $this->expectException(MonkwardException::class);
        $this->expectExceptionMessage('at most one path');

        Arguments::parse(['monkward', 'a', 'b']);
    }

    #[Test]
    public function rejectsInvalidPort(): void
    {
        $this->expectException(MonkwardException::class);

        Arguments::parse(['monkward', '--port=abc']);
    }
}
