<?php

namespace Wexample\SymfonySeo\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SitemapControllerTest extends WebTestCase
{
    public function testThePagesAreListedInEveryLanguage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));

        $document = new \DOMDocument();
        $document->loadXML($response->getContent());
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xpath->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

        $locations = array_map(
            static fn (\DOMNode $node): string => $node->textContent,
            iterator_to_array($xpath->query('//s:url/s:loc'))
        );

        $this->assertSame(
            ['http://localhost/en/page', 'http://localhost/fr/page', 'http://localhost/plain'],
            $locations
        );

        $alternates = [];

        foreach ($xpath->query('//s:url[s:loc="http://localhost/fr/page"]/xhtml:link') as $link) {
            $alternates[$link->getAttribute('hreflang')] = $link->getAttribute('href');
        }

        $this->assertSame([
            'en' => 'http://localhost/en/page',
            'fr' => 'http://localhost/fr/page',
            'x-default' => 'http://localhost/en/page',
        ], $alternates);
    }
}
