<?php

declare(strict_types=1);

namespace Monkward\Tests;

use Monkward\Config\UserConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HttpIntegrationTest extends TestCase
{
    private string $target = '';
    private string $workspace = '';

    /** @var resource|null */
    private $process = null;

    /** @var array<int, resource> */
    private array $pipes = [];

    private int $port = 0;

    protected function setUp(): void
    {
        $this->target = \sys_get_temp_dir() . '/monkward-http-target-' . \bin2hex(\random_bytes(6));
        $this->workspace = \sys_get_temp_dir() . '/monkward-http-ws-' . \bin2hex(\random_bytes(6));

        \mkdir($this->target . '/docs/deep', 0777, true);
        \mkdir($this->target . '/empty', 0777, true);
        \mkdir($this->target . '/vendor', 0777, true);
        \mkdir($this->target . '/node_modules', 0777, true);
        \mkdir($this->workspace . '/docroot', 0777, true);

        \file_put_contents($this->target . '/hello.md', "# Hello\n\nworld");
        \file_put_contents($this->target . '/notes.txt', 'plain root file');
        \file_put_contents($this->target . '/docs/guide.md', "# Guide\n\n- one\n- two");
        \file_put_contents($this->target . '/docs/deep/nested.md', "# Nested\n\n> deep");
        \file_put_contents($this->target . '/docs/notes.txt', 'not markdown');
        \file_put_contents($this->target . '/empty/nothing.md.txt', 'nope');
        \file_put_contents($this->target . '/vendor/bundle.md', '# Vendored');
        \file_put_contents($this->target . '/node_modules/pkg.md', '# Packaged');
    }

    protected function tearDown(): void
    {
        $this->stopServer();
        $this->removeTree($this->target);
        $this->removeTree($this->workspace);
    }

    #[Test]
    public function servesShallowListingsDocumentsAssetsAnd404s(): void
    {
        $this->startServer();

        // Root shows one level: all subdirs and all file names.
        [$indexStatus, $indexBody] = $this->get('/');
        self::assertSame(200, $indexStatus);
        self::assertStringContainsString('monkward', $indexBody);
        self::assertStringContainsString('docs/', $indexBody);
        self::assertStringContainsString('empty/', $indexBody);
        self::assertStringContainsString('vendor/', $indexBody);
        self::assertStringContainsString('node_modules/', $indexBody);
        self::assertStringContainsString('>hello.md</a>', $indexBody);
        self::assertStringContainsString('<span>notes.txt</span>', $indexBody);

        // Subdirectory listings go one level down.
        [$docsStatus, $docsBody] = $this->get('/docs/');
        self::assertSame(200, $docsStatus);
        self::assertStringContainsString('deep/', $docsBody);
        self::assertStringContainsString('>guide.md</a>', $docsBody);
        self::assertStringContainsString('<span>notes.txt</span>', $docsBody);

        [$deepStatus, $deepBody] = $this->get('/docs/deep/');
        self::assertSame(200, $deepStatus);
        self::assertStringContainsString('>nested.md</a>', $deepBody);

        // Markdown documents still render at their full path.
        [$docStatus, $docBody] = $this->get('/hello.md');
        self::assertSame(200, $docStatus);
        self::assertStringContainsString('<h1 id="hello">Hello</h1>', $docBody);
        self::assertStringContainsString('<p>world</p>', $docBody);

        [$nestedStatus, $nestedBody] = $this->get('/docs/deep/nested.md');
        self::assertSame(200, $nestedStatus);
        self::assertStringContainsString('<h1 id="nested">Nested</h1>', $nestedBody);
        self::assertStringContainsString('<blockquote>', $nestedBody);

        [$cssStatus, $cssBody] = $this->get('/monkward-theme.css');
        self::assertSame(200, $cssStatus);
        self::assertStringContainsString('monkward default theme', $cssBody);

        [$faviconStatus, $faviconBody] = $this->get('/favicon.svg');
        self::assertSame(200, $faviconStatus);
        self::assertStringContainsString('<svg', $faviconBody);

        [$missingStatus] = $this->get('/missing.md');
        self::assertSame(404, $missingStatus);

        [$vendorDirStatus, $vendorDirBody] = $this->get('/vendor/');
        self::assertSame(200, $vendorDirStatus);
        self::assertStringContainsString('>bundle.md</a>', $vendorDirBody);

        [$vendorDocStatus, $vendorDocBody] = $this->get('/vendor/bundle.md');
        self::assertSame(200, $vendorDocStatus);
        self::assertStringContainsString('<h1 id="vendored">Vendored</h1>', $vendorDocBody);

        [$nonMdStatus] = $this->get('/docs/notes.txt');
        self::assertSame(404, $nonMdStatus);

        [$fileAsDirStatus] = $this->get('/notes.txt');
        self::assertSame(404, $fileAsDirStatus);
    }

    #[Test]
    public function headingIdsCanBeDisabledViaEnv(): void
    {
        $this->startServer(extraEnv: ['MONKWARD_HEADING_IDS' => '0']);

        [$status, $body] = $this->get('/hello.md');
        self::assertSame(200, $status);
        self::assertStringContainsString('<h1>Hello</h1>', $body);
        self::assertStringNotContainsString('id="hello"', $body);
    }

    #[Test]
    public function singleFileModeServesOnlyThatFile(): void
    {
        \file_put_contents($this->target . '/only.md', "# Only\n\njust this one");

        $this->startServer($this->target . '/only.md');

        [$indexStatus, $indexBody] = $this->get('/');
        self::assertSame(200, $indexStatus);
        self::assertStringContainsString('<h1 id="only">Only</h1>', $indexBody);

        [$otherStatus] = $this->get('/hello.md');
        self::assertSame(404, $otherStatus);

        [$dirStatus] = $this->get('/docs/');
        self::assertSame(404, $dirStatus);
    }

    private function startServer(?string $singleFile = null, array $extraEnv = []): void
    {
        do {
            $this->port = \random_int(20000, 45000);
        } while ($this->isPortOpen($this->port));

        $command = [
            \PHP_BINARY,
            '-d', 'display_errors=0',
            '-S', '127.0.0.1:' . $this->port,
            '-t', $this->workspace . '/docroot',
            \dirname(__DIR__) . '/bin/server.php',
        ];

        $env = \array_merge(\getenv(), [
            'MONKWARD_TARGET' => $this->target,
            'MONKWARD_SINGLE_FILE' => $singleFile ?? '',
            'MONKWARD_THEME_CSS_PATH' => \dirname(__DIR__) . '/resources/themes/default.css',
            'MONKWARD_HEADING_IDS' => '1',
        ], $extraEnv);

        $process = \proc_open($command, [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, null, $env);

        if (! \is_resource($process)) {
            self::fail('could not start php -S');
        }

        $this->process = $process;
        $this->pipes = [$pipes[1], $pipes[2]];
        \stream_set_blocking($this->pipes[0], false);
        \stream_set_blocking($this->pipes[1], false);

        $deadline = \microtime(true) + 5.0;
        while (\microtime(true) < $deadline) {
            $status = \proc_get_status($process);
            if (! $status['running']) {
                self::fail('php -S exited early');
            }
            if ($this->isPortOpen($this->port)) {
                return;
            }
            \usleep(100_000);
        }

        self::fail('php -S did not start in time');
    }

    private function stopServer(): void
    {
        if (\is_resource($this->process)) {
            @\proc_terminate($this->process);
            foreach ($this->pipes as $pipe) {
                if (\is_resource($pipe)) {
                    \fclose($pipe);
                }
            }
            @\proc_close($this->process);
        }
        $this->process = null;
        $this->pipes = [];
    }

    private function isPortOpen(int $port): bool
    {
        $socket = @\fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
        if ($socket === false) {
            return false;
        }
        \fclose($socket);
        return true;
    }

    /** @return array{int, string} */
    private function get(string $path): array
    {
        $bodyFile = \tempnam(\sys_get_temp_dir(), 'monkward-http-test-');
        if ($bodyFile === false) {
            self::fail('could not create temp file');
        }
        $url = 'http://127.0.0.1:' . $this->port . $path;
        $output = \shell_exec('curl -s -o ' . \escapeshellarg($bodyFile) . ' -w "%{http_code}" ' . \escapeshellarg($url) . ' 2>&1');
        $status = (int) \trim((string) $output);
        $body = (string) \file_get_contents($bodyFile);
        \unlink($bodyFile);

        return [$status, $body];
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
