<?php

declare(strict_types=1);

namespace Monkward\Tests;

use Monkward\Config\UserConfig;
use Monkward\Site\DirectoryLister;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DirectoryListerTest extends TestCase
{
    private string $root = '';

    protected function setUp(): void
    {
        $this->root = \sys_get_temp_dir() . '/monkward-lister-test-' . \bin2hex(\random_bytes(6));
        \mkdir($this->root . '/docs', 0777, true);
        \mkdir($this->root . '/empty', 0777, true);
        \mkdir($this->root . '/vendor', 0777, true);
        \mkdir($this->root . '/node_modules', 0777, true);

        \file_put_contents($this->root . '/hello.md', '# hello');
        \file_put_contents($this->root . '/notes.txt', 'plain');
        \file_put_contents($this->root . '/.env', 'hidden file');
        \file_put_contents($this->root . '/docs/guide.md', '# guide');
        \file_put_contents($this->root . '/vendor/bundle.md', '# vendored');
        \file_put_contents($this->root . '/node_modules/pkg.md', '# packaged');
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    #[Test]
    public function listsOneLevelWithDirectoriesFirstThenMarkdownThenOtherFiles(): void
    {
        $lister = new DirectoryLister($this->root, UserConfig::DEFAULT_IGNORE);
        $listing = $lister->list('');

        self::assertSame(['docs', 'empty'], \array_column($listing['dirs'], 'name'));
        self::assertSame(['hello.md', '.env', 'notes.txt'], \array_column($listing['files'], 'name'));

        self::assertSame('/docs/', $listing['dirs'][0]['href']);
        self::assertSame('/hello.md', $listing['files'][0]['href']);
        self::assertTrue($listing['files'][0]['md']);
        self::assertFalse($listing['files'][1]['md']);
        self::assertNull($listing['files'][1]['href']);
    }

    #[Test]
    public function listsSubdirectoriesOneLevelAtATime(): void
    {
        $lister = new DirectoryLister($this->root, UserConfig::DEFAULT_IGNORE);

        $docs = $lister->list('docs');
        self::assertSame([], $docs['dirs']);
        self::assertSame(['guide.md'], \array_column($docs['files'], 'name'));

        $empty = $lister->list('empty');
        self::assertSame([], $empty['dirs']);
        self::assertSame([], $empty['files']);
    }

    #[Test]
    public function ignoredDirectoryNamesAreSkipped(): void
    {
        $lister = new DirectoryLister($this->root, UserConfig::DEFAULT_IGNORE);
        $names = \array_column($lister->list('')['dirs'], 'name');

        self::assertNotContains('vendor', $names);
        self::assertNotContains('node_modules', $names);
    }

    #[Test]
    public function isIgnoredPathMatchesDirectorySegments(): void
    {
        $lister = new DirectoryLister($this->root, UserConfig::DEFAULT_IGNORE);

        self::assertTrue($lister->isIgnoredPath('vendor/bundle.md'));
        self::assertTrue($lister->isIgnoredPath('a/node_modules/b.md'));
        self::assertFalse($lister->isIgnoredPath('docs/guide.md'));
    }

    #[Test]
    public function isIgnoredDirChecksTheDirectoryItself(): void
    {
        $lister = new DirectoryLister($this->root, UserConfig::DEFAULT_IGNORE);

        self::assertTrue($lister->isIgnoredDir('vendor'));
        self::assertTrue($lister->isIgnoredDir('a/vendor/b'));
        self::assertFalse($lister->isIgnoredDir('docs'));
        self::assertFalse($lister->isIgnoredDir(''));
    }

    #[Test]
    public function resolveDirAndFileStayWithinRoot(): void
    {
        $lister = new DirectoryLister($this->root, UserConfig::DEFAULT_IGNORE);

        self::assertSame($this->root . '/docs', $lister->resolveDir('docs'));
        self::assertSame($this->root . '/docs/guide.md', $lister->resolveFile('docs/guide.md'));
        self::assertNull($lister->resolveDir('docs/guide.md'));
        self::assertNull($lister->resolveFile('../outside.md'));
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
