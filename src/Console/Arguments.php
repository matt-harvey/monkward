<?php

declare(strict_types=1);

namespace Monkward\Console;

use Monkward\MonkwardException;

final readonly class Arguments
{
    public function __construct(
        public ?string $theme = null,
        public ?int $port = null,
        public ?string $host = null,
        public bool $open = false,
        public bool $help = false,
        public bool $version = false,
        public bool $init = false,
        public ?string $path = null,
    ) {
    }

    public static function parse(array $argv): self
    {
        $theme = null;
        $port = null;
        $host = null;
        $open = false;
        $help = false;
        $version = false;
        $init = false;
        $path = null;
        $positionalOnly = false;

        $args = \array_slice($argv, 1);
        $count = \count($args);

        for ($i = 0; $i < $count; $i++) {
            $arg = $args[$i];

            if ($positionalOnly) {
                self::setPath($arg, $path);
                continue;
            }

            if ($arg === '--') {
                $positionalOnly = true;
                continue;
            }

            if ($arg === '-h' || $arg === '--help') {
                $help = true;
                continue;
            }

            if ($arg === '-V' || $arg === '--version') {
                $version = true;
                continue;
            }

            if ($arg === '--init') {
                $init = true;
                continue;
            }

            if ($arg === '--open') {
                $open = true;
                continue;
            }

            if (\str_starts_with($arg, '--theme=')) {
                $theme = self::requiredValue('--theme', \substr($arg, 8));
                continue;
            }
            if ($arg === '--theme') {
                $theme = self::requiredValue('--theme', self::nextValue($args, $i));
                $i++;
                continue;
            }

            if (\str_starts_with($arg, '--port=')) {
                $port = self::parsePort('--port', \substr($arg, 7));
                continue;
            }
            if ($arg === '--port') {
                $port = self::parsePort('--port', self::nextValue($args, $i));
                $i++;
                continue;
            }

            if (\str_starts_with($arg, '--host=')) {
                $host = self::requiredValue('--host', \substr($arg, 7));
                continue;
            }
            if ($arg === '--host') {
                $host = self::requiredValue('--host', self::nextValue($args, $i));
                $i++;
                continue;
            }

            if (\str_starts_with($arg, '-')) {
                throw new MonkwardException("unknown option: $arg (try --help)");
            }

            self::setPath($arg, $path);
        }

        return new self(
            theme: $theme,
            port: $port,
            host: $host,
            open: $open,
            help: $help,
            version: $version,
            init: $init,
            path: $path,
        );
    }

    private static function setPath(string $value, ?string &$path): void
    {
        if ($path !== null) {
            throw new MonkwardException('expected at most one path argument');
        }
        $path = $value;
    }

    /** @param list<string> $args */
    private static function nextValue(array $args, int $index): string
    {
        $value = $args[$index + 1] ?? null;
        if ($value === null || $value === '') {
            throw new MonkwardException("option {$args[$index]} requires a value");
        }
        return $value;
    }

    private static function requiredValue(string $option, string $value): string
    {
        if ($value === '') {
            throw new MonkwardException("option {$option} requires a value");
        }
        return $value;
    }

    private static function parsePort(string $option, string $value): int
    {
        if (! \preg_match('/^\d+$/', $value)) {
            throw new MonkwardException("invalid {$option} value: {$value}");
        }
        $port = (int) $value;
        if ($port < 1 || $port > 65535) {
            throw new MonkwardException("{$option} must be between 1 and 65535");
        }
        return $port;
    }
}
