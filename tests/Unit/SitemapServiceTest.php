<?php

namespace Wexample\SymfonySeo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\AccessMap;
use Wexample\SymfonySeo\Service\RobotsService;
use Wexample\SymfonySeo\Service\SitemapService;

class SitemapServiceTest extends TestCase
{
    private function createService(?AccessMap $accessMap): SitemapService
    {
        $routes = new RouteCollection();
        $routes->add('open', new Route('/open'));
        $routes->add('closed', new Route('/closed'));
        $routes->add('public', new Route('/public'));

        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $parameters, int $type): string => 'http://localhost'.$routes->get($name)->getPath()
        );

        return new SitemapService(
            $router,
            $this->createStub(RobotsService::class),
            true,
            [],
            $accessMap
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
}
