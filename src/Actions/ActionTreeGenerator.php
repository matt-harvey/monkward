<?php

declare(strict_types=1);

namespace Monkward\Actions;

final class ActionTreeGenerator
{
    /** Deepest nested directory (not counting the markdown file itself) that monkward will route. */
    public const MAX_DEPTH = 64;

    public function generate(string $workspace): string
    {
        $root = $workspace . '/actions';

        if (! \is_dir($root) && ! \mkdir($root, 0700, true) && ! \is_dir($root)) {
            throw new \RuntimeException("could not create actions directory: {$root}");
        }

        \file_put_contents("{$root}/_root.get.php", $this->rootAction());
        \file_put_contents("{$root}/[file].get.php", $this->leafAction(0));

        $dir = $root;
        for ($depth = 1; $depth <= self::MAX_DEPTH; $depth++) {
            $dir .= "/[dir{$depth}]";
            if (! \is_dir($dir) && ! \mkdir($dir, 0700, true) && ! \is_dir($dir)) {
                throw new \RuntimeException("could not create actions directory: {$dir}");
            }
            \file_put_contents("{$dir}/[file].get.php", $this->leafAction($depth));
        }

        return $root;
    }

    private function rootAction(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use Monkward\Http\IndexPage;

return static function (IndexPage $indexPage): array {
    return $indexPage->data();
};
PHP;
    }

    private function leafAction(int $depth): string
    {
        $loop = $depth === 0
            ? ''
            : 'for ($i = 1; $i <= ' . $depth . '; $i++) {' . "\n"
                . '        $parts[] = $p["dir" . $i];' . "\n"
                . '    }' . "\n";

        return <<<PHP
<?php

declare(strict_types=1);

use Monkward\Http\AssetResponder;
use Monkward\Http\DocPage;
use SubstancePHP\HTTP\RequestParams\PathParams;
use SubstancePHP\HTTP\Respond;

return static function (
    PathParams \$p,
    DocPage \$docPage,
    AssetResponder \$assets,
    Respond \$respond,
): mixed {
    \$file = (string) (\$p['file'] ?? '');

    if (\$assets->isAsset(\$file)) {
        return \$assets->respond(\$file, \$respond);
    }

    \$parts = [];
{$loop}    \$parts[] = \$file;

    return \$docPage->render(\implode('/', \$parts));
};
PHP;
    }
}
