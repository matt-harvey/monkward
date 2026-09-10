<?php

declare(strict_types=1);

namespace Monkward\Http;

use SubstancePHP\HTTP\Application;
use SubstancePHP\HTTP\Middleware\ExceptionHandlerMiddleware;
use SubstancePHP\HTTP\SubstanceProvider;

final class Server
{
    public function run(): void
    {
        $root = \dirname(__DIR__, 2);

        $app = Application::make(
            env: [],
            actionRoot: '', // unused: monkward supplies its own route middlewares
            templateRoot: $root . '/app/templates',
            providers: [
                SubstanceProvider::class,
                MonkwardProvider::class,
            ],
            middlewares: [
                ExceptionHandlerMiddleware::class,
                MonkwardRouteMatcherMiddleware::class,
                MonkwardRouteActorMiddleware::class,
            ],
            htmlEncoding: 'UTF-8',
        );

        $app->execute();
    }
}
