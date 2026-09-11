<?php

declare(strict_types=1);

namespace Monkward\Config;

use Monkward\MonkwardException;

final readonly class UserConfig
{
    public const DEFAULT_THEME = 'light';
    public const DEFAULT_PORT = 8800;
    public const DEFAULT_HOST = '127.0.0.1';

    public function __construct(
        public ?string $theme = null,
        public ?int $port = null,
        public ?string $host = null,
        public bool $headingIds = true,
    ) {}

    public static function load(): self
    {
        $configDir = self::configDir();
        $file = $configDir === null ? null : "{$configDir}/config.toml";
        if ($file === null || ! \is_file($file)) {
            return new self();
        }

        $contents = @\file_get_contents($file);
        if ($contents === false) {
            return new self();
        }

        return self::fromToml($contents);
    }

    public static function fromToml(string $contents): self
    {
        $values = self::parseToml($contents);

        $theme = null;
        if (isset($values['theme']) && $values['theme'] !== '') {
            $theme = $values['theme'];
        }

        $port = null;
        if (isset($values['port'])) {
            $port = self::parsePort((string) $values['port']);
        }

        $host = null;
        if (isset($values['host']) && $values['host'] !== '') {
            $host = $values['host'];
        }

        $headingIds = true;
        if (isset($values['heading_ids'])) {
            $headingIds = self::parseBool($values['heading_ids']);
        }

        return new self($theme, $port, $host, $headingIds);
    }

    public static function configDir(): ?string
    {
        $base = \getenv('XDG_CONFIG_HOME');
        if ($base === false || $base === '') {
            $home = \getenv('HOME');
            if ($home === false || $home === '') {
                return null;
            }
            $base = $home . '/.config';
        }
        return \rtrim($base, '/') . '/monkward';
    }

    public static function themesDir(): string
    {
        $configDir = self::configDir();
        if ($configDir === null) {
            throw new MonkwardException('could not determine config directory (is HOME set?)');
        }
        return $configDir . '/themes';
    }

    /**
     * Parses the small TOML subset monkward understands: top-level `key = "value"` /
     * `key = 'value'` / `key = 123` lines, with `#` comments. Everything else is ignored.
     *
     * @return array<string, string>
     */
    private static function parseToml(string $contents): array
    {
        $values = [];

        foreach (\explode("\n", $contents) as $line) {
            $line = \trim($line);
            if ($line === '' || \str_starts_with($line, '#')) {
                continue;
            }
            if (\str_starts_with($line, '[')) {
                continue;
            }
            if (! \str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = \explode('=', $line, 2);
            $key = \trim($key);
            if ($key === '') {
                continue;
            }

            $value = self::unquote(self::stripInlineComment(\trim($value)));
            $values[$key] = $value;
        }

        return $values;
    }

    private static function stripInlineComment(string $value): string
    {
        if (\str_starts_with($value, '"')) {
            $end = \strrpos($value, '"');
            return $end === false || $end === 0 ? $value : \substr($value, 0, $end + 1);
        }
        if (\str_starts_with($value, "'")) {
            $end = \strrpos($value, "'");
            return $end === false || $end === 0 ? $value : \substr($value, 0, $end + 1);
        }
        $stripped = \preg_replace('/\s+#.*$/', '', $value);
        return $stripped ?? $value;
    }

    private static function unquote(string $value): string
    {
        $length = \strlen($value);
        if ($length >= 2 && $value[0] === '"' && $value[$length - 1] === '"') {
            return \substr($value, 1, -1);
        }
        if ($length >= 2 && $value[0] === "'" && $value[$length - 1] === "'") {
            return \substr($value, 1, -1);
        }
        return $value;
    }

    private static function parsePort(string $value): int
    {
        if (! \preg_match('/^\d+$/', $value)) {
            throw new MonkwardException("invalid port in config.toml: {$value}");
        }
        $port = (int) $value;
        if ($port < 1 || $port > 65535) {
            throw new MonkwardException('port in config.toml must be between 1 and 65535');
        }
        return $port;
    }

    private static function parseBool(string $value): bool
    {
        return match (\strtolower(\trim($value))) {
            'true', '1', 'yes', 'on' => true,
            'false', '0', 'no', 'off' => false,
            default => throw new MonkwardException("invalid boolean in config.toml: {$value}"),
        };
    }
}
