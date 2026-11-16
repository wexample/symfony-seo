<?php

namespace Wexample\SymfonySeo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Wexample\SymfonySeo\Controller\SitemapController;
use Wexample\SymfonySeo\Service\RobotsService;
use Wexample\SymfonySeo\Service\SitemapService;

class SitemapControllerTest extends TestCase
{
    private function createController(int $maxUrls): SitemapController
    {
        $routes = new RouteCollection();

        foreach (['one', 'two', 'three'] as $name) {
            $routes->add($name, new Route('/'.$name));
        }

        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $parameters): string => 'http://localhost'.$routes->get($name)->getPath()
        );

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $name, array $parameters): string => 'http://localhost/sitemap-'.$parameters['file'].'.xml'
        );

        return new SitemapController(
            new SitemapService($router, $this->createStub(RobotsService::class), true, [], null, [], $maxUrls),
            $urlGenerator
        );
    }

    /**
     * @return string[]
     */
    private function locations(string $xml, string $element): array
    {
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return array_map(
            static fn (\DOMNode $node): string => $node->textContent,
            iterator_to_array($xpath->query('//s:'.$element.'/s:loc'))
        );
    }

    public function testBeyondWhatAFileListsTheSitemapIsAnIndexOfItsFiles(): void
    {
        $controller = $this->createController(2);

        $this->assertSame(
            ['http://localhost/sitemap-1.xml', 'http://localhost/sitemap-2.xml'],
            $this->locations($controller->sitemap()->getContent(), 'sitemap')
        );
        $this->assertSame(
            ['http://localhost/one', 'http://localhost/two'],
            $this->locations($controller->file(1)->getContent(), 'url')
        );
        $this->assertSame(
            ['http://localhost/three'],
            $this->locations($controller->file(2)->getContent(), 'url')
        );
    }

    public function testAFilePastTheLastIsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->createController(2)->file(3);
    }

    public function testAnUnsplitSitemapHasNoFiles(): void
    {
        $controller = $this->createController(50000);

        $this->assertCount(3, $this->locations($controller->sitemap()->getContent(), 'url'));

        $this->expectException(NotFoundHttpException::class);
        $controller->file(1);
    }
}
