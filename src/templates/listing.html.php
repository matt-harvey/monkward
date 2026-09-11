<?php

declare(strict_types=1);

use Monkward\Site\Breadcrumb;
use Monkward\Site\DirEntry;
use Monkward\Site\FileEntry;
use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var bool $single */
/** @var array{title: string, rel: string, html: string, crumbs: list<Breadcrumb>}|null $view */
/** @var string $rootName */
/** @var string $relDir */
/** @var list<Breadcrumb> $breadcrumbs */
/** @var list<DirEntry> $dirs */
/** @var list<FileEntry> $files */
?>

<?php
if ($single) {
    \assert($view !== null);
    $this->layout('layout', ['title' => $view['title']]);
} else {
    $this->layout('layout', ['title' => $rootName]);
    $this->start('site-nav');
    ?>
    <a class="crumb" href="/">index</a>
    <?php foreach ($breadcrumbs as $crumb): ?>
        <span class="crumb-sep">/</span>
        <a class="crumb" href="<?= $this->a($crumb->href) ?>"><?= $this->h($crumb->label) ?></a>
    <?php endforeach; ?>
<?php
    $this->stop();
}
?>

<?php if ($single): ?>
    <?= $this->partial('doc-body', $view) ?>
<?php elseif ($dirs === [] && $files === []): ?>
    <div class="index">
        <h1><?= $this->h($rootName) ?></h1>
        <p class="empty">This directory is empty.</p>
    </div>
<?php else: ?>
    <div class="index">
        <h1><?= $this->h($rootName) ?></h1>
        <ul class="tree">
            <?php foreach ($dirs as $dir): ?>
                <li class="tree-dir">
                    <a class="dir-link"
                        href="<?= $this->a($dir->href) ?>"
                    ><?= $this->h($dir->name) ?>/</a>
                </li>
            <?php endforeach; ?>
            <?php foreach ($files as $file): ?>
                <?php if ($file->md): ?>
                    <li class="tree-file">
                        <a class="md-link"
                            href="<?= $this->a($file->href) ?>"
                        ><?= $this->h($file->name) ?></a>
                    </li>
                <?php else: ?>
                    <li class="tree-file plain"><span><?= $this->h($file->name) ?></span></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
