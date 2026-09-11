<?php

declare(strict_types=1);

namespace Monkward\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\ContextFactoryInterface;
use SubstancePHP\HTTP\Exception\BaseException\RoutingException;
use SubstancePHP\HTTP\RendererFactoryInterface;
use SubstancePHP\HTTP\RequestParams\QueryParams;
use SubstancePHP\HTTP\Respond;

/**
 * Executes the matched MonkwardRoute and converts its return value into a PSR-7
 * response, mirroring the stock RouteActorMiddleware but without filepath-based
 * Route objects.
 */
final readonly class MonkwardRouteActorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ContextFactoryInterface $contextFactory,
        private RendererFactoryInterface $rendererFactory,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $route = $request->getAttribute(MonkwardRoute::class);
        if (! $route instanceof MonkwardRoute) {
            throw new RoutingException('Route attribute not an instance of ' . MonkwardRoute::class);
        }

        $context = $this->contextFactory->createContext($this->container, $request);

        $responseData = match ($route->kind) {
            MonkwardRoute::KIND_LISTING => $context->run(
                static fn(ListingPage $listingPage): array => $listingPage->data($route->relativePath ?? ''),
            ),
            MonkwardRoute::KIND_DOC => $context->run(
                static fn(DocPage $docPage): array => $docPage->render($route->relativePath ?? ''),
            ),
            MonkwardRoute::KIND_ASSET => $context->run(
                static fn(AssetResponder $assets, Respond $respond, QueryParams $query): mixed
                    => $assets->respond($route->assetName ?? '', $respond, $query),
            ),
            default => throw new RoutingException('Unhandled route kind: ' . $route->kind),
        };

        /** @var Respond $respond */
        $respond = $context->get(Respond::class);

        $response = $this->responseFactory
            ->createResponse($respond->statusCode)
            ->withHeader('Content-Type', $respond->contentType);

        if ($request->getMethod() !== 'HEAD') {
            $renderer = $this->rendererFactory->createRenderer(
                $route->template,
                $respond->contentType,
                $responseData,
            );
            $response->getBody()->write($renderer->render());
        }

        return $response;
    }
}
