<?php

declare(strict_types=1);

namespace Monkward\Http;

use Monkward\Markdown\MarkdownRenderer;
use Monkward\Site\FileScanner;
use Monkward\Site\SiteIndex;
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
            'monkward.ignore' => static fn (): array => self::decodeList(\getenv('MONKWARD_IGNORE')),

            MarkdownRenderer::class => Container::autowire(...),

            FileScanner::class => static fn (Container $c): FileScanner => new FileScanner(
                ignore: $c->get('monkward.ignore'),
            ),

            SiteIndex::class => static fn (Container $c): SiteIndex => new SiteIndex(
                root: $c->get('monkward.target'),
                scanner: $c->get(FileScanner::class),
            ),

            DocPage::class => static fn (Container $c): DocPage => new DocPage(
                root: $c->get('monkward.target'),
                singleFile: $c->get('monkward.single-file'),
                markdown: $c->get(MarkdownRenderer::class),
                scanner: $c->get(FileScanner::class),
            ),

            IndexPage::class => static fn (Container $c): IndexPage => new IndexPage(
                root: $c->get('monkward.target'),
                singleFile: $c->get('monkward.single-file'),
                siteIndex: $c->get(SiteIndex::class),
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

    /** @return list<string> */
    private static function decodeList(string|false $env): array
    {
        if ($env === false || $env === '') {
            return [];
        }

        $decoded = \json_decode($env, true);
        if (! \is_array($decoded)) {
            return [];
        }

        return \array_values(\array_filter($decoded, 'is_string'));
    }
}
