<?php

declare(strict_types=1);

$autoload = $_composer_autoload_path
    ?? (\is_file(__DIR__ . '/../vendor/autoload.php')
        ? __DIR__ . '/../vendor/autoload.php'
        : __DIR__ . '/../../../autoload.php');

if (! \is_file($autoload)) {
    \fwrite(\STDERR, 'monkward: unable to locate the Composer autoloader.' . \PHP_EOL);
    exit(1);
}

require $autoload;
