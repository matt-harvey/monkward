<?php

declare(strict_types=1);

namespace Monkward\Http;

use Monkward\Markdown\MarkdownRenderer;
use Monkward\Site\DirectoryLister;
use Psr\Http\Message\ResponseFactoryInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\ContextFactoryInterface;
use SubstancePHP\HTTP\EnvironmentInterface;
use SubstancePHP\HTTP\Middleware\RouteActorMiddleware;
use SubstancePHP\HTTP\Middleware\RouteMatcherMiddleware;
use SubstancePHP\HTTP\ProviderInterface;
use SubstancePHP\HTTP\RendererFactory;
use SubstancePHP\HTTP\RendererFactoryInterface;

final class MonkwardProvider implements ProviderInterface
{
    #[\Override]
    public static function factories(EnvironmentInterface $environment): array
    {
        return [
            'monkward.target' => static fn (): string => \getenv('MONKWARD_TARGET') ?: '',
            'monkward.single-file' => static fn (): ?string => \getenv('MONKWARD_SINGLE_FILE') ?: null,
            'monkward.theme-css-path' => static fn (): string => \getenv('MONKWARD_THEME_CSS_PATH') ?: '',
            'monkward.heading-ids' => static fn (): bool => self::decodeBool(\getenv('MONKWARD_HEADING_IDS'), true),

            MarkdownRenderer::class => static fn (Container $c): MarkdownRenderer => new MarkdownRenderer(
                headingIds: $c->get('monkward.heading-ids'),
            ),

            DirectoryLister::class => static fn (Container $c): DirectoryLister => new DirectoryLister(
                root: $c->get('monkward.target'),
            ),

            DocPage::class => static fn (Container $c): DocPage => new DocPage(
                root: $c->get('monkward.target'),
                singleFile: $c->get('monkward.single-file'),
                markdown: $c->get(MarkdownRenderer::class),
                lister: $c->get(DirectoryLister::class),
            ),

            ListingPage::class => static fn (Container $c): ListingPage => new ListingPage(
                root: $c->get('monkward.target'),
                singleFile: $c->get('monkward.single-file'),
                lister: $c->get(DirectoryLister::class),
                docPage: $c->get(DocPage::class),
            ),

            AssetResponder::class => static fn (Container $c): AssetResponder => new AssetResponder(
                themeCssPath: $c->get('monkward.theme-css-path'),
            ),

            RendererFactoryInterface::class => static fn (Container $c): MonkwardRendererFactory => new MonkwardRendererFactory(
                new RendererFactory(
                    templateRoot: $c->get('substance.template-root'),
                    htmlEncoding: $c->get('substance.html-encoding'),
                    defaultLayout: $c->get('substance.default-layout'),
                ),
            ),

            RouteMatcherMiddleware::class => static fn (Container $c): MonkwardRouteMatcherMiddleware => new MonkwardRouteMatcherMiddleware(
                assets: $c->get(AssetResponder::class),
            ),

            RouteActorMiddleware::class => static fn (Container $c): MonkwardRouteActorMiddleware => new MonkwardRouteActorMiddleware(
                container: $c,
                contextFactory: $c->get(ContextFactoryInterface::class),
                rendererFactory: $c->get(RendererFactoryInterface::class),
                responseFactory: $c->get(ResponseFactoryInterface::class),
            ),
        ];
    }

    private static function decodeBool(string|false $env, bool $default): bool
    {
        if ($env === false || $env === '') {
            return $default;
        }

        return $env === '1' || $env === 'true' || $env === 'yes';
    }
}
