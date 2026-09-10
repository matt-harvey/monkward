<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var string $title */
/** @var string $rel */
/** @var string $html */
/** @var array $crumbs */

$this->layout('layout', ['title' => $title]);
$this->start('site-nav');
?><a class="crumb" href="/">index</a><?php
foreach ($crumbs as $crumb) {
    ?><span class="crumb-sep">/</span><a class="crumb" href="<?= $this->a($crumb['href']) ?>"><?= $this->h($crumb['label']) ?></a><?php
}
$this->stop();
?>
<?= $this->partial('doc-body', ['title' => $title, 'rel' => $rel, 'html' => $html]) ?>
