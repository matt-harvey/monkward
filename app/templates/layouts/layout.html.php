<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var string $title */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $this->h($title) ?> · monkward</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/monkward-theme.css">
</head>
<body>
<header class="site-header">
  <div class="site-header-inner">
    <a class="brand" href="/">monkward</a>
    <nav class="site-nav"><?= $this->fetch('site-nav') ?></nav>
  </div>
</header>
<main class="site-main">
<?= $this->content() ?>
</main>
<footer class="site-footer">
  <span>rendered on the fly · monkward</span>
</footer>
</body>
</html>
