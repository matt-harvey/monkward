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
<link rel="stylesheet" id="theme-style" href="/monkward-theme.css">
</head>
<body>
<header class="site-header">
  <div class="site-header-inner">
    <a class="brand" href="/">monkward</a>
    <nav class="site-nav"><?= $this->fetch('site-nav') ?></nav>
    <label class="theme-picker">
      <span class="theme-picker-label">theme</span>
      <select id="theme-select" class="theme-select" aria-label="Theme"></select>
    </label>
  </div>
</header>
<main class="site-main">
<?= $this->content() ?>
</main>
<footer class="site-footer">
  <span>rendered on the fly · monkward</span>
</footer>
<script>
(function () {
  var KEY = 'monkward-theme';
  var select = document.getElementById('theme-select');
  var link = document.getElementById('theme-style');

  function apply(name) {
    link.href = '/monkward-theme.css?theme=' + encodeURIComponent(name);
  }

  fetch('/monkward-themes.json')
    .then(function (response) { return response.json(); })
    .then(function (data) {
      var saved = localStorage.getItem(KEY);
      var current = saved && data.themes.indexOf(saved) !== -1 ? saved : data.default;

      data.themes.forEach(function (name) {
        var option = document.createElement('option');
        option.value = name;
        option.textContent = name;
        if (name === current) {
          option.selected = true;
        }
        select.appendChild(option);
      });

      apply(current);

      select.addEventListener('change', function () {
        localStorage.setItem(KEY, select.value);
        apply(select.value);
      });
    })
    .catch(function () {
      /* theme list unavailable; keep the server default */
    });
})();
</script>
</body>
</html>
