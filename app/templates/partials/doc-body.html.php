<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var string $title */
/** @var string $rel */
/** @var string $html */
?>
<article class="doc">
  <header class="doc-header">
    <h1><?= $this->h($title) ?></h1>
    <p class="doc-path"><?= $this->h($rel) ?></p>
  </header>
  <div class="markdown-body">
    <?= $this->raw($html) ?>
  </div>
</article>
