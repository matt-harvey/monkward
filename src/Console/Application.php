<?php

declare(strict_types=1);

namespace Monkward\Console;

use Monkward\Config\ConfigInstaller;
use Monkward\Config\Theme;
use Monkward\Config\ThemeManager;
use Monkward\Config\UserConfig;
use Monkward\MonkwardException;
use Monkward\Version;

final class Application
{
    private string $workspace = '';

    /** @var resource|null */
    private $serverProcess = null;

    /** @var array<int, resource> */
    private array $serverPipes = [];

    private bool $stopRequested = false;

    public function run(array $argv): int
    {
        try {
            $args = Arguments::parse($argv);

            if ($args->help) {
                $this->printHelp();
                return 0;
            }

            if ($args->version) {
                $this->stdout('monkward ' . Version::VERSION);
                return 0;
            }

            if ($args->init) {
                return $this->initConfig();
            }

            $created = (new ConfigInstaller())->ensureInstalled();
            if ($created !== []) {
                $this->stdout('Initialized monkward configuration in ' . UserConfig::configDir());
            }

            $config = UserConfig::load();

            $themeName = $args->theme ?? $config->theme;
            $port = $args->port ?? $config->port ?? UserConfig::DEFAULT_PORT;
            $host = $args->host ?? $config->host ?? UserConfig::DEFAULT_HOST;
            $ignore = \array_merge($config->ignore, $args->ignore);

            $theme = (new ThemeManager())->resolve($themeName, strict: $args->theme !== null);

            [$target, $singleFile] = $this->resolveTarget($args->path);

            $this->workspace = $this->createWorkspace();
            $this->registerShutdown();
            $this->registerSignalHandlers();

            $url = \sprintf('http://%s:%d', $host, $port);
            $this->startServer($host, $port, $this->routerPath(), $target, $singleFile, $theme->path, $ignore);

            $this->stdout(\sprintf('monkward %s serving %s at %s', Version::VERSION, $target, $url));
            $this->stdout('Press Ctrl+C to stop.');

            if (! $args->noBrowser) {
                $this->openBrowser($url);
            }

            $this->waitForServer();

            return 0;
        } catch (MonkwardException $e) {
            $this->stderr('monkward: ' . $e->getMessage());
            return 1;
        } catch (\Throwable $e) {
            $this->stderr('monkward: ' . $e->getMessage());
            return 1;
        }
    }

    private function initConfig(): int
    {
        if (UserConfig::configDir() === null) {
            $this->stderr('monkward: could not determine config directory (is HOME set?)');
            return 1;
        }

        $created = (new ConfigInstaller())->ensureInstalled();

        if ($created === []) {
            $this->stdout('monkward configuration already set up in ' . UserConfig::configDir());
            return 0;
        }

        $this->stdout('Initialized monkward configuration:');
        foreach ($created as $path) {
            $this->stdout('  created ' . $path);
        }

        return 0;
    }

    /** @return array{string, ?string} */
    private function resolveTarget(?string $path): array
    {
        if ($path === null) {
            $path = \getcwd();
            if ($path === false) {
                throw new MonkwardException('could not determine the current directory');
            }
        }

        $real = \realpath($path);
        if ($real === false) {
            throw new MonkwardException("path not found: {$path}");
        }

        if (\is_dir($real)) {
            return [$real, null];
        }

        if (\is_file($real) && \strtolower(\pathinfo($real, \PATHINFO_EXTENSION)) === 'md') {
            return [\dirname($real), $real];
        }

        throw new MonkwardException("not a directory or a .md file: {$path}");
    }

    private function createWorkspace(): string
    {
        $workspace = \sys_get_temp_dir() . '/monkward-' . \getmypid() . '-' . \bin2hex(\random_bytes(4));

        if (! \mkdir($workspace, 0700, true) && ! \is_dir($workspace)) {
            throw new MonkwardException("could not create temporary workspace: {$workspace}");
        }

        if (! \mkdir($workspace . '/docroot', 0700, true) && ! \is_dir($workspace . '/docroot')) {
            throw new MonkwardException("could not create temporary workspace: {$workspace}/docroot");
        }

        return $workspace;
    }

    private function routerPath(): string
    {
        $phar = \Phar::running(false);
        if ($phar !== '') {
            return $phar;
        }
        return \dirname(__DIR__, 2) . '/bin/server.php';
    }

    private function startServer(
        string $host,
        int $port,
        string $router,
        string $target,
        ?string $singleFile,
        string $themeCssPath,
        array $ignore,
    ): void {
        $command = [
            \PHP_BINARY,
            '-d', 'display_errors=0',
            '-d', 'log_errors=1',
            '-S', "{$host}:{$port}",
            '-t', $this->workspace . '/docroot',
            $router,
        ];

        $env = \array_merge(\getenv(), [
            'MONKWARD_TARGET' => $target,
            'MONKWARD_SINGLE_FILE' => $singleFile ?? '',
            'MONKWARD_THEME_CSS_PATH' => $themeCssPath,
            'MONKWARD_IGNORE' => \json_encode(\array_values($ignore), \JSON_THROW_ON_ERROR),
        ]);

        $descriptors = [
            0 => ['file', self::nullDevice(), 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @\proc_open($command, $descriptors, $pipes, null, $env);
        if (! \is_resource($process)) {
            throw new MonkwardException('failed to start the PHP built-in server');
        }

        \stream_set_blocking($pipes[1], false);
        \stream_set_blocking($pipes[2], false);

        $this->serverProcess = $process;
        $this->serverPipes = [$pipes[1], $pipes[2]];

        $deadline = \microtime(true) + 5.0;
        while (\microtime(true) < $deadline) {
            $status = \proc_get_status($process);

            if (! $status['running']) {
                $output = $this->drainServerOutput();
                $this->stopServer();
                throw new MonkwardException(
                    'the server exited before it was ready'
                    . ($output === '' ? '' : ":\n" . \rtrim($output)),
                );
            }

            if ($this->isPortOpen($host, $port)) {
                return;
            }

            $this->relayServerOutput();
            \usleep(100_000);
        }

        $this->stopServer();
        throw new MonkwardException('the server did not start within 5 seconds');
    }

    private function waitForServer(): void
    {
        if (! \is_resource($this->serverProcess)) {
            return;
        }

        while (! $this->stopRequested) {
            $status = \proc_get_status($this->serverProcess);
            if (! $status['running']) {
                break;
            }
            $this->relayServerOutput();
            \usleep(100_000);
        }

        $this->relayServerOutput();
        $this->stopServer();
    }

    private function relayServerOutput(): void
    {
        foreach ($this->serverPipes as $index => $pipe) {
            if (! \is_resource($pipe)) {
                continue;
            }
            $chunk = \stream_get_contents($pipe);
            if ($chunk === false || $chunk === '') {
                continue;
            }
            if ($index === 0) {
                $this->stdout($chunk);
            } else {
                $this->stderr($chunk);
            }
        }
    }

    private function drainServerOutput(): string
    {
        $output = '';
        foreach ($this->serverPipes as $pipe) {
            if (! \is_resource($pipe)) {
                continue;
            }
            $chunk = \stream_get_contents($pipe);
            if ($chunk !== false && $chunk !== '') {
                $output .= $chunk;
            }
        }
        return $output;
    }

    private function stopServer(): void
    {
        if (\is_resource($this->serverProcess)) {
            @\proc_terminate($this->serverProcess);
            foreach ($this->serverPipes as $pipe) {
                if (\is_resource($pipe)) {
                    \fclose($pipe);
                }
            }
            @\proc_close($this->serverProcess);
        }

        $this->serverProcess = null;
        $this->serverPipes = [];
    }

    private function isPortOpen(string $host, int $port): bool
    {
        $errno = 0;
        $errstr = '';
        $socket = @\fsockopen($host, $port, $errno, $errstr, 0.25);
        if ($socket === false) {
            return false;
        }
        \fclose($socket);
        return true;
    }

    private static function nullDevice(): string
    {
        return \PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
    }

    private function openBrowser(string $url): void
    {
        $browser = \getenv('BROWSER');
        if ($browser !== false && $browser !== '') {
            $parts = \preg_split('/\s+/', \trim($browser));
            $this->launchDetached(\array_merge($parts === false ? [] : $parts, [$url]));
            return;
        }

        if (\PHP_OS_FAMILY === 'Darwin') {
            $this->launchDetached(['open', $url]);
            return;
        }

        if (\PHP_OS_FAMILY === 'Windows') {
            $this->launchDetached(['cmd', '/c', 'start', '', $url]);
            return;
        }

        foreach (['xdg-open', 'sensible-browser', 'x-www-browser', 'www-browser'] as $candidate) {
            if ($this->commandExists($candidate)) {
                $this->launchDetached([$candidate, $url]);
                return;
            }
        }

        $this->stdout("Could not find a program to open a browser; open {$url} manually.");
    }

    /** @param list<string> $command */
    private function launchDetached(array $command): void
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            $process = @\proc_open($command, [
                0 => ['file', 'NUL', 'r'],
                1 => ['file', 'NUL', 'w'],
                2 => ['file', 'NUL', 'w'],
            ], $pipes);
            if (\is_resource($process)) {
                @\proc_close($process);
            }
            return;
        }

        $shell = \implode(' ', \array_map('escapeshellarg', $command));
        @\shell_exec($shell . ' > /dev/null 2>&1 &');
    }

    private function commandExists(string $command): bool
    {
        $path = \getenv('PATH') ?: '';
        foreach (\explode(\PATH_SEPARATOR, $path) as $dir) {
            if ($dir === '') {
                continue;
            }
            $candidate = \rtrim($dir, '/\\') . \DIRECTORY_SEPARATOR . $command;
            if (\is_executable($candidate)) {
                return true;
            }
        }
        return false;
    }

    private function registerShutdown(): void
    {
        \register_shutdown_function(function (): void {
            $this->stopServer();
            $this->removeWorkspace();
        });
    }

    private function registerSignalHandlers(): void
    {
        if (! \function_exists('pcntl_signal')) {
            return;
        }

        \pcntl_async_signals(true);
        \pcntl_signal(\SIGINT, function (): void {
            $this->stopRequested = true;
        });
        \pcntl_signal(\SIGTERM, function (): void {
            $this->stopRequested = true;
        });
    }

    private function removeWorkspace(): void
    {
        if ($this->workspace === '' || ! \is_dir($this->workspace)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->workspace, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                @\rmdir($item->getPathname());
            } else {
                @\unlink($item->getPathname());
            }
        }

        @\rmdir($this->workspace);
    }

    private function printHelp(): void
    {
        $this->stdout(<<<HELP
monkward — an on-the-fly Markdown viewer for your current directory

Usage:
  monkward [options] [path]

Arguments:
  path                     A directory to serve recursively, or a single .md file.
                           Defaults to the current directory.

Options:
  --theme=NAME             Use ~/.config/monkward/themes/NAME.css
  --port=PORT              Port to serve on (default: 8800)
  --host=HOST              Host to bind (default: 127.0.0.1)
  --ignore=NAME            Also ignore this directory name for this run (repeatable)
  --init                   Create ~/.config/monkward with config.toml and themes/default.css
  --no-browser             Do not open the default browser
  -h, --help               Show this help
  -V, --version            Show the version

Configuration lives in ~/.config/monkward/config.toml (created on install or
first run). The `ignore` key is the full list of directory names to skip, e.g.:

      ignore = [".git", "vendor", "node_modules"]

Theme stylesheets live in ~/.config/monkward/themes/ (e.g. yeah.css); the
default theme is copied there as default.css so it can be edited.
HELP);
    }

    private function stdout(string $message): void
    {
        \fwrite(\STDOUT, $message . \PHP_EOL);
    }

    private function stderr(string $message): void
    {
        \fwrite(\STDERR, $message . \PHP_EOL);
    }
}
