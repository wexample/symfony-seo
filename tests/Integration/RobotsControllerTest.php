<?php

namespace Wexample\SymfonySeo\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wexample\SymfonySeo\Controller\RobotsController;

class RobotsControllerTest extends WebTestCase
{
    public function testTheRouteServesTheProvidersAndTheAppConfiguration(): void
    {
        $client = static::createClient();
        $client->request('GET', '/robots.txt');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));

        $body = $response->getContent();
        // Every fixture provider is collected through autoconfiguration alone.
        $this->assertStringContainsString("Allow: /private/public/\n", $body);
        $this->assertStringContainsString("Disallow: /tunnel/\n", $body);
        $this->assertStringContainsString("User-agent: Googlebot\nDisallow: /no-google/\n", $body);
        $this->assertStringContainsString("Crawl-delay: 10\n", $body);
        $this->assertStringEndsWith("Sitemap: https://example.com/sitemap.xml\n", $body);
    }

    public function testTheRouteNameCarriesNoUnderscorePrefix(): void
    {
        $client = static::createClient();

        $this->assertSame(
            '/robots.txt',
            $client->getContainer()->get('router')->generate(RobotsController::ROUTE_ROBOTS)
        );
    }
}
