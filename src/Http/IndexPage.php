<?php

declare(strict_types=1);

namespace Monkward\Http;

use Monkward\Site\SiteIndex;

final class IndexPage
{
    public function __construct(
        private string $root,
        private ?string $singleFile,
        private SiteIndex $siteIndex,
        private DocPage $docPage,
    ) {
    }

    /** @return array{single: bool, view: array|null, rootName: string, fileCount: int, tree: array} */
    public function data(): array
    {
        if ($this->singleFile !== null) {
            return [
                'single' => true,
                'view' => $this->docPage->render(\basename($this->singleFile)),
                'rootName' => \basename($this->singleFile),
                'fileCount' => 1,
                'tree' => [],
            ];
        }

        return [
            'single' => false,
            'view' => null,
            'rootName' => \basename(\rtrim($this->root, '/\\')) ?: $this->root,
            'fileCount' => $this->siteIndex->count(),
            'tree' => $this->siteIndex->tree(),
        ];
    }
}
