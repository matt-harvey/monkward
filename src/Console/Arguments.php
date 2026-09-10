<?php

declare(strict_types=1);

namespace Monkward\Console;

use Monkward\MonkwardException;

final readonly class Arguments
{
    /** @var array<string, string> option => value key */
    private const VALUE_OPTIONS = [
        '--theme' => 'theme',
        '--port' => 'port',
        '--host' => 'host',
    ];

    /** @var array<string, string> flag => flag key */
    private const FLAG_OPTIONS = [
        '-h' => 'help',
        '--help' => 'help',
        '-V' => 'version',
        '--version' => 'version',
        '--init' => 'init',
        '--open' => 'open',
    ];

    public function __construct(
        public ?string $theme = null,
        public ?int $port = null,
        public ?string $host = null,
        public bool $open = false,
        public bool $help = false,
        public bool $version = false,
        public bool $init = false,
        public ?string $path = null,
    ) {}

    /** @param list<string> $argv */
    public static function parse(array $argv): self
    {
        $theme = null;
        $port = null;
        $host = null;
        $flags = ['open' => false, 'help' => false, 'version' => false, 'init' => false];
        $path = null;
        $positionalOnly = false;

        /** @var list<string> $args */
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

            if (isset(self::FLAG_OPTIONS[$arg])) {
                $flags[self::FLAG_OPTIONS[$arg]] = true;
                continue;
            }

            $match = self::matchValueOption($arg, $args, $i);
            if ($match !== null) {
                $i += $match['consumedExtra'] ? 1 : 0;
                switch ($match['key']) {
                    case 'port':
                        $port = self::parsePort($match['option'], $match['value']);
                        break;
                    case 'host':
                        $host = self::requiredValue($match['option'], $match['value']);
                        break;
                    default:
                        $theme = self::requiredValue($match['option'], $match['value']);
                }
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
            open: $flags['open'],
            help: $flags['help'],
            version: $flags['version'],
            init: $flags['init'],
            path: $path,
        );
    }

    /**
     * Matches a value-taking option in either `--option=value` or `--option value` form.
     *
     * @param list<string> $args
     * @return array{option: string, key: string, value: string, consumedExtra: bool}|null
     */
    private static function matchValueOption(string $arg, array $args, int $index): ?array
    {
        foreach (self::VALUE_OPTIONS as $option => $key) {
            if ($arg === $option) {
                return [
                    'option' => $option,
                    'key' => $key,
                    'value' => self::nextValue($args, $index),
                    'consumedExtra' => true,
                ];
            }

            $prefix = $option . '=';
            if (\str_starts_with($arg, $prefix)) {
                return [
                    'option' => $option,
                    'key' => $key,
                    'value' => \substr($arg, \strlen($prefix)),
                    'consumedExtra' => false,
                ];
            }
        }

        return null;
    }

    /** @param-out string $path */
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
