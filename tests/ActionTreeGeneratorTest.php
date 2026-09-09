<?php

declare(strict_types=1);

namespace Monkward\Tests;

use Monkward\Actions\ActionTreeGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ActionTreeGeneratorTest extends TestCase
{
    private string $workspace = '';

    protected function setUp(): void
    {
        $this->workspace = \sys_get_temp_dir() . '/monkward-actions-test-' . \bin2hex(\random_bytes(6));
        \mkdir($this->workspace, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->workspace);
    }

    #[Test]
    public function generatesRootAndDepthLimitedLeafActions(): void
    {
        $root = (new ActionTreeGenerator())->generate($this->workspace);

        self::assertFileExists($root . '/_root.get.php');
        self::assertFileExists($root . '/[file].get.php');
        self::assertFileExists($root . '/[dir1]/[file].get.php');
        self::assertFileExists($root . '/[dir1]/[dir2]/[file].get.php');
        $deepPath = \implode('/', \array_map(static fn (int $i): string => "[dir{$i}]", \range(1, ActionTreeGenerator::MAX_DEPTH))) . '/[file].get.php';
        self::assertFileExists($root . '/' . $deepPath);
    }

    #[Test]
    public function generatedLeafActionIsValidPhp(): void
    {
        $root = (new ActionTreeGenerator())->generate($this->workspace);

        $lint = \shell_exec('php -l ' . \escapeshellarg($root . '/[dir1]/[file].get.php') . ' 2>&1');
        self::assertIsString($lint);
        self::assertStringContainsString('No syntax errors', $lint);
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
