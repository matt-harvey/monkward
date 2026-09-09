<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var bool $single */
/** @var array|null $view */
/** @var string $rootName */
/** @var int $fileCount */
/** @var array $tree */

if ($single) {
    \assert($view !== null);
    $this->layout('layout', ['title' => $view['title']]);
} else {
    $this->layout('layout', ['title' => $rootName]);
    $this->start('site-nav');
    ?><span class="nav-count"><?= $this->h((string) $fileCount) ?> markdown file<?= $fileCount === 1 ? '' : 's' ?></span><?php
    $this->stop();
}
?>
<?php if ($single): ?>
  <?= $this->partial('doc-body', $view) ?>
<?php elseif ($fileCount === 0): ?>
  <div class="index">
    <h1><?= $this->h($rootName) ?></h1>
    <p class="empty">No markdown files found in this directory.</p>
  </div>
<?php else: ?>
  <div class="index">
    <h1><?= $this->h($rootName) ?></h1>
    <?= $this->partial('file-tree', ['nodes' => $tree]) ?>
  </div>
<?php endif; ?>
