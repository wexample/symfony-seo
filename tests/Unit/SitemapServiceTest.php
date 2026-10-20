<?php

namespace Wexample\SymfonySeo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\AccessMap;
use Wexample\SymfonySeo\Class\SitemapRoute;
use Wexample\SymfonySeo\Interface\SitemapProviderInterface;
use Wexample\SymfonySeo\Service\RobotsService;
use Wexample\SymfonySeo\Service\SitemapService;

class SitemapServiceTest extends TestCase
{
    /**
     * @param SitemapProviderInterface[] $providers
     */
    private function createService(?AccessMap $accessMap, array $providers = []): SitemapService
    {
        $routes = new RouteCollection();
        $routes->add('open', new Route('/open'));
        $routes->add('closed', new Route('/closed'));
        $routes->add('public', new Route('/public'));
        $routes->add('article', new Route('/{_locale}/articles/{slug}', ['_locale' => 'en'], ['_locale' => 'en|fr']));

        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $parameters, int $type): string => 'http://localhost'.strtr(
                $routes->get($name)->getPath(),
                array_combine(
                    array_map(static fn (string $key): string => '{'.$key.'}', array_keys($parameters)),
                    array_map('strval', $parameters)
                ) ?: []
            )
        );

        return new SitemapService(
            $router,
            $this->createStub(RobotsService::class),
            true,
            [],
            $accessMap,
            $providers
        );
    }

    /**
     * @return string[]
     */
    private function urls(SitemapService $service): array
    {
        return array_map(static fn ($entry): string => $entry->url, $service->getEntries());
    }

    public function testAPageTheAccessControlClosesIsLeftOut(): void
    {
        $accessMap = new AccessMap();
        $accessMap->add(new PathRequestMatcher('^/closed'), ['ROLE_ADMIN']);
        $accessMap->add(new PathRequestMatcher('^/public'), ['PUBLIC_ACCESS']);

        $this->assertSame(
            ['http://localhost/open', 'http://localhost/public'],
            $this->urls($this->createService($accessMap))
        );
    }

    public function testWithoutSecurityEveryPageIsListed(): void
    {
        $this->assertSame(
            ['http://localhost/open', 'http://localhost/closed', 'http://localhost/public'],
            $this->urls($this->createService(null))
        );
    }

    public function testARouteWithAParameterIsListedOnlyThroughAProvider(): void
    {
        $this->assertNotContains('http://localhost/en/articles/{slug}', $this->urls($this->createService(null)));

        $provider = new class () implements SitemapProviderInterface {
            public function getSitemapRoutes(): iterable
            {
                yield new SitemapRoute('article', ['slug' => 'hello']);
            }
        };
        $entries = array_values(array_filter(
            $this->createService(null, [$provider])->getEntries(),
            static fn ($entry): bool => str_contains($entry->url, '/articles/')
        ));

        $this->assertSame(
            ['http://localhost/en/articles/hello', 'http://localhost/fr/articles/hello'],
            array_map(static fn ($entry): string => $entry->url, $entries)
        );
        $this->assertSame('http://localhost/en/articles/hello', $entries[1]->alternates['x-default']);
    }
}
