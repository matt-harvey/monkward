<?php

declare(strict_types=1);

namespace Monkward\Http;

use SubstancePHP\HTTP\Application;
use SubstancePHP\HTTP\Middleware\ExceptionHandlerMiddleware;
use SubstancePHP\HTTP\Middleware\RouteActorMiddleware;
use SubstancePHP\HTTP\Middleware\RouteMatcherMiddleware;
use SubstancePHP\HTTP\SubstanceProvider;

final class Server
{
    public function run(): void
    {
        $root = \dirname(__DIR__, 2);

        $app = Application::make(
            env: [],
            actionRoot: \getenv('MONKWARD_ACTIONS') ?: $root . '/app/actions',
            templateRoot: $root . '/app/templates',
            providers: [
                SubstanceProvider::class,
                MonkwardProvider::class,
            ],
            middlewares: [
                ExceptionHandlerMiddleware::class,
                RouteMatcherMiddleware::class,
                RouteActorMiddleware::class,
            ],
            htmlEncoding: 'UTF-8',
        );

        $app->execute();
    }
}
