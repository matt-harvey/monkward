<?php

declare(strict_types=1);

namespace Monkward\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\Exception\BaseException\UserError;

/**
 * Classifies a request into one of monkward's three route kinds and attaches the
 * result to the request for the route actor. This replaces the stock
 * RouteMatcherMiddleware (which resolves routes from action files on disk).
 */
final readonly class MonkwardRouteMatcherMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AssetResponder $assets,
    ) {
    }

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $method = $request->getMethod();
        if ($method !== 'GET' && $method !== 'HEAD') {
            UserError::throw(405);
        }

        $route = $this->match($request->getUri()->getPath());

        return $handler->handle($request->withAttribute(MonkwardRoute::class, $route));
    }

    private function match(string $path): MonkwardRoute
    {
        $trimmed = \trim($path, '/');

        if ($trimmed === '') {
            return MonkwardRoute::listing('');
        }

        $decoded = [];
        foreach (\explode('/', $trimmed) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                UserError::throw(404);
            }
            $decoded[] = \rawurldecode($segment);
        }

        if (\count($decoded) === 1 && $this->assets->isAsset($decoded[0])) {
            return MonkwardRoute::asset($decoded[0]);
        }

        $last = $decoded[\count($decoded) - 1];
        if (\str_ends_with(\strtolower($last), '.md')) {
            return MonkwardRoute::doc(\implode('/', $decoded));
        }

        return MonkwardRoute::listing(\implode('/', $decoded));
    }
}
