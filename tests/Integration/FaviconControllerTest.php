<?php

namespace Wexample\SymfonySeo\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FaviconControllerTest extends WebTestCase
{
    public function testTheRouteServesTheBundledIcon(): void
    {
        $client = static::createClient();
        $client->request('GET', '/favicon.ico');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/x-icon', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(
            realpath(__DIR__.'/../../src/Resources/favicon/favicon.ico'),
            $response->getFile()->getRealPath()
        );
    }
}
