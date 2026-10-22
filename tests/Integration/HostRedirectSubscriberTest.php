<?php

namespace Wexample\SymfonySeo\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HostRedirectSubscriberTest extends WebTestCase
{
    public function testARedirectedHostLandsOnTheTargetWhateverThePath(): void
    {
        $client = static::createClient();
        $client->request('GET', 'http://old-docs.example.com/some/old/page?x=1');

        $response = $client->getResponse();
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://example.com/', $response->headers->get('Location'));
    }

    public function testKeepPathCarriesThePathAndTheQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', 'http://moved.example.com/some/page?x=1');

        $response = $client->getResponse();
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://example.com/some/page?x=1', $response->headers->get('Location'));
    }

    public function testAnotherHostIsServedAsUsual(): void
    {
        $client = static::createClient();
        $client->request('GET', 'http://localhost/favicon.ico');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
}
