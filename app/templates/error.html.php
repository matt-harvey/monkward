<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var string $error */
/** @var int $statusCode */

$this->layout('layout', ['title' => (string) $statusCode]);
?>
<div class="error-page">
  <h1><?= $this->h((string) $statusCode) ?></h1>
  <p><?= $this->h($error) ?></p>
  <p><a href="/">back to index</a></p>
</div>
