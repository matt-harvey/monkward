#!/usr/bin/env php
<?php

Phar::mapPhar('monkward.phar');

if (PHP_SAPI === 'cli-server') {
    require 'phar://monkward.phar/bin/server.php';
} else {
    require 'phar://monkward.phar/bin/monkward';
}

__HALT_COMPILER();
