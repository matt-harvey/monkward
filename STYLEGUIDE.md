# Monkward style guide

PHP formatting, imports, quoting, and the 110-column line limit are enforced by
`composer lint:check` (PHP CS Fixer + PHP_CodeSniffer). Everything below is
kept by hand.

## Templates (`.html.php`)

- 4-space indentation for markup; no tabs.
- Opening and closing tags sit in the same column.
- Every template opens with a PHP header block: `declare(strict_types=1)`,
  `use` imports, and the `@var` docblock for the variables it receives.
  Close the block with `?>`, followed by one blank line before the markup.
- PHP control statements get their own `<?php ... ?>` lines and use the
  alternative syntax inside the template body (`if:` / `endif;`,
  `foreach:` / `endforeach;`).
- Output uses `<?= ... ?>`.
- Always escape through the renderer helpers (`h()`, `a()`, `u()`, `raw()`).
- Keep PHP blocks and template blocks visually separated with blank lines.

## Inline JavaScript

- `const` / `let`, never `var`.
- Arrow functions and other modern ES features are fine.
- 4-space indentation; script content is indented one level relative to its
  `<script>` tag, and `<script>` / `</script>` share a column.
