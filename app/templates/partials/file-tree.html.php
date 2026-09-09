<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var array $nodes */
?>
<ul class="tree">
<?php foreach ($nodes as $node): ?>
<?php if ($node['type'] === 'dir'): ?>
  <li class="tree-dir">
    <span class="dir-name"><?= $this->h($node['name']) ?>/</span>
    <?= $this->partial('file-tree', ['nodes' => $node['children']]) ?>
  </li>
<?php else: ?>
  <li class="tree-file">
    <a href="<?= $this->a($node['href']) ?>"><?= $this->h($node['label']) ?></a>
  </li>
<?php endif; ?>
<?php endforeach; ?>
</ul>
