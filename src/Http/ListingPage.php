<?php

declare(strict_types=1);

namespace Monkward\Http;

use Monkward\Site\DirectoryLister;
use SubstancePHP\HTTP\Exception\BaseException\UserError;

final class ListingPage
{
    public function __construct(
        private string $root,
        private ?string $singleFile,
        private DirectoryLister $lister,
        private DocPage $docPage,
    ) {}

    /** @return array{single: bool, view: array|null, rootName: string, relDir: string, breadcrumbs: array, dirs: array, files: array} */
    public function data(string $relativeDir): array
    {
        if ($this->singleFile !== null) {
            if ($relativeDir !== '') {
                UserError::throw(404);
            }

            return [
                'single' => true,
                'view' => $this->docPage->render(\basename($this->singleFile)),
                'rootName' => \basename($this->singleFile),
                'relDir' => '',
                'breadcrumbs' => [],
                'dirs' => [],
                'files' => [],
            ];
        }

        if ($this->lister->resolveDir($relativeDir) === null) {
            UserError::throw(404);
        }

        $listing = $this->lister->list($relativeDir);

        return [
            'single' => false,
            'view' => null,
            'rootName' => $relativeDir === ''
                ? (\basename(\rtrim($this->root, '/\\')) ?: $this->root)
                : \basename($relativeDir),
            'relDir' => $relativeDir,
            'breadcrumbs' => $this->breadcrumbs($relativeDir),
            'dirs' => $listing['dirs'],
            'files' => $listing['files'],
        ];
    }

    /** @return list<array{label: string, href: string}> */
    private function breadcrumbs(string $relativeDir): array
    {
        if ($relativeDir === '') {
            return [];
        }

        $crumbs = [];
        $prefix = '';
        foreach (\explode('/', $relativeDir) as $part) {
            $prefix = $prefix === '' ? $part : $prefix . '/' . $part;
            $crumbs[] = [
                'label' => $part,
                'href' => '/' . DirectoryLister::encodePath($prefix) . '/',
            ];
        }

        return $crumbs;
    }
}
