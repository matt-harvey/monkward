<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var string $title */
/** @var string $rel */
/** @var string $dir */
/** @var string $html */

$this->layout('layout', ['title' => $title]);
$this->start('site-nav');
?><a class="crumb" href="/">index</a><?php
if ($dir !== '') {
    ?><span class="crumb-sep">/</span><span class="crumb"><?= $this->h($dir) ?></span><?php
}
$this->stop();
?>
<?= $this->partial('doc-body', ['title' => $title, 'rel' => $rel, 'dir' => $dir, 'html' => $html]) ?>
