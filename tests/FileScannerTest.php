<?php

declare(strict_types=1);

namespace Monkward\Tests;

use Monkward\Site\FileScanner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FileScannerTest extends TestCase
{
    private string $root = '';

    protected function setUp(): void
    {
        $this->root = \sys_get_temp_dir() . '/monkward-test-' . \bin2hex(\random_bytes(6));
        \mkdir($this->root . '/docs/deep', 0777, true);
        \mkdir($this->root . '/empty', 0777, true);
        \mkdir($this->root . '/.git', 0777, true);
        \mkdir($this->root . '/vendor', 0777, true);
        \mkdir($this->root . '/node_modules', 0777, true);

        \file_put_contents($this->root . '/hello.md', '# hello');
        \file_put_contents($this->root . '/zebra.md', '# zebra');
        \file_put_contents($this->root . '/docs/guide.md', '# guide');
        \file_put_contents($this->root . '/docs/deep/a.md', '# a');
        \file_put_contents($this->root . '/docs/notes.txt', 'not markdown');
        \file_put_contents($this->root . '/.git/config.md', '# hidden');
        \file_put_contents($this->root . '/.hidden.md', '# hidden');
        \file_put_contents($this->root . '/vendor/bundle.md', '# vendored');
        \file_put_contents($this->root . '/node_modules/pkg.md', '# packaged');
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    #[Test]
    public function findsMarkdownRecursivelyAndPrunesIrrelevantDirs(): void
    {
        $files = (new FileScanner())->scan($this->root);

        self::assertSame([
            'docs/deep/a.md',
            'docs/guide.md',
            'hello.md',
            'zebra.md',
        ], $files);
    }

    #[Test]
    public function includeReIncludesADefaultIgnoredDirectory(): void
    {
        $files = (new FileScanner(include: ['vendor']))->scan($this->root);

        self::assertContains('vendor/bundle.md', $files);
        self::assertNotContains('node_modules/pkg.md', $files);
    }

    #[Test]
    public function extraIgnoreSkipsAnAdditionalDirectory(): void
    {
        \mkdir($this->root . '/build', 0777, true);
        \file_put_contents($this->root . '/build/notes.md', '# notes');

        $default = (new FileScanner())->scan($this->root);
        self::assertContains('build/notes.md', $default);

        $ignored = (new FileScanner(ignore: ['build']))->scan($this->root);
        self::assertNotContains('build/notes.md', $ignored);
    }

    #[Test]
    public function isIgnoredMatchesDirectorySegments(): void
    {
        $scanner = new FileScanner();

        self::assertTrue($scanner->isIgnored('vendor/bundle.md'));
        self::assertTrue($scanner->isIgnored('a/node_modules/b.md'));
        self::assertFalse($scanner->isIgnored('docs/guide.md'));
        self::assertFalse((new FileScanner(include: ['vendor']))->isIgnored('vendor/bundle.md'));
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
