<?php

declare(strict_types=1);

namespace Monkward\Site;

final class SiteIndex
{
    public function __construct(
        private string $root,
        private FileScanner $scanner,
    ) {
    }

    /** @return list<string> */
    public function files(): array
    {
        return $this->scanner->scan($this->root);
    }

    public function count(): int
    {
        return \count($this->files());
    }

    /** @return list<array{type: string, name: string, path: string, href: string, label?: string, children?: array}> */
    public function tree(): array
    {
        $root = [
            'type' => 'dir',
            'name' => '',
            'path' => '',
            'children' => [],
        ];

        foreach ($this->files() as $relative) {
            $parts = \explode('/', $relative);
            $last = \count($parts) - 1;
            $node = &$root;

            foreach ($parts as $depth => $part) {
                if ($depth === $last) {
                    $node['children'][] = [
                        'type' => 'file',
                        'name' => $part,
                        'label' => \preg_replace('/\.md$/i', '', $part) ?? $part,
                        'path' => $relative,
                        'href' => '/' . \implode('/', \array_map('rawurlencode', \explode('/', $relative))),
                    ];
                    continue;
                }

                $childIndex = null;
                foreach ($node['children'] as $index => $child) {
                    if ($child['type'] === 'dir' && $child['name'] === $part) {
                        $childIndex = $index;
                        break;
                    }
                }

                if ($childIndex === null) {
                    $node['children'][] = [
                        'type' => 'dir',
                        'name' => $part,
                        'path' => \implode('/', \array_slice($parts, 0, $depth + 1)),
                        'children' => [],
                    ];
                    $childIndex = \array_key_last($node['children']);
                }

                $node = &$node['children'][$childIndex];
            }

            unset($node);
        }

        $this->sortNodes($root['children']);

        return $root['children'];
    }

    /** @param array<int, array{type: string, name: string}> $nodes */
    private function sortNodes(array &$nodes): void
    {
        \usort($nodes, static function (array $a, array $b): int {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }
            return \strnatcasecmp($a['name'], $b['name']);
        });

        foreach ($nodes as &$node) {
            if ($node['type'] === 'dir' && isset($node['children'])) {
                $this->sortNodes($node['children']);
            }
        }
        unset($node);
    }
}
